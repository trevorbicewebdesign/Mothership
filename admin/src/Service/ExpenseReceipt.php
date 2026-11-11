<?php

namespace TrevorBice\Component\Mothership\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Table\ExpenseTable;

\defined('_JEXEC') or die;

/**
 * The vendor's invoice or receipt behind an expense: the file itself, kept with the expense.
 *
 * "New from PDF" parks an uploaded file, has ExpenseReader propose the expense, and opens the
 * expense form filled in from it. Nothing is written to an expense until the administrator saves;
 * the file is then moved next to the other receipts and recorded in #__mothership_attachments
 * with context 'expense'. Files sit above the web root and are only ever
 * served through the expense controller, to administrators.
 *
 * A file is stored under the hash of its bytes, which is how the same invoice uploaded twice is
 * caught.
 */
class ExpenseReceipt
{
    public const CONTEXT = 'expense';

    /** The currency expenses are kept in. Amounts in any other are left for the administrator. */
    public const HOME_CURRENCY = 'USD';

    /** How many files one upload may hold. */
    public const MAX_FILES = 20;

    /** How long an upload nobody saved is kept. */
    public const PENDING_TTL = 172800;

    /** Days either side of a date within which the same vendor and amount is the same purchase. */
    public const SIMILAR_DAYS = 5;

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    /** Where receipts are kept: above the web root. */
    public static function storePath(): string
    {
        return dirname(JPATH_ROOT) . '/mothership/expenses/receipts';
    }

    /**
     * Park an upload while the administrator reviews the expense. Returns the token that names
     * it, or null when the file is not one that can be kept.
     */
    public static function stash(string $path, string $originalName): ?string
    {
        $mime = ExpenseReader::sniff($path);

        if ($mime === null) {
            return null;
        }

        $dir   = self::pendingDir();
        $token = bin2hex(random_bytes(16));

        if (!@copy($path, $dir . '/' . $token . '.bin')) {
            throw new \RuntimeException('The file could not be saved for import.');
        }

        file_put_contents($dir . '/' . $token . '.json', json_encode([
            'name' => self::cleanName($originalName, ExpenseReader::TYPES[$mime]),
            'mime' => $mime,
        ]));

        return $token;
    }

    /**
     * The parked upload, or null when the token is malformed or the file has gone.
     *
     * @return object{path: string, name: string, mime: string}|null
     */
    public static function pending(string $token): ?object
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $path = self::storePath() . '/pending/' . $token . '.bin';
        $meta = json_decode((string) @file_get_contents(self::storePath() . '/pending/' . $token . '.json'), true);

        if (!is_file($path) || !\is_array($meta) || !isset(ExpenseReader::TYPES[$meta['mime'] ?? ''])) {
            return null;
        }

        return (object) ['path' => $path, 'name' => (string) ($meta['name'] ?? 'receipt'), 'mime' => (string) $meta['mime']];
    }

    /**
     * Drop parked uploads that will not be entered after all.
     *
     * @param string[] $tokens
     */
    public static function forget(array $tokens): void
    {
        foreach ($tokens as $token) {
            if (preg_match('/^[a-f0-9]{32}$/', (string) $token)) {
                @unlink(self::storePath() . '/pending/' . $token . '.bin');
                @unlink(self::storePath() . '/pending/' . $token . '.json');
            }
        }
    }

    /**
     * The expenses that already carry this very file, oldest first.
     *
     * @return int[]
     */
    public static function carrying(string $path): array
    {
        $db   = self::db();
        $like = sha1_file($path) . '.%';

        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('e.id'))
            ->from($db->quoteName('#__mothership_attachments', 'a'))
            ->join('INNER', $db->quoteName('#__mothership_expenses', 'e') . ' ON ' . $db->quoteName('e.id') . ' = ' . $db->quoteName('a.resource_id'))
            ->where($db->quoteName('a.context') . ' = ' . $db->quote(self::CONTEXT))
            ->where($db->quoteName('a.filename') . ' LIKE :name')
            ->order($db->quoteName('e.id') . ' ASC')
            ->bind(':name', $like);

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    /**
     * The expense form, filled from what ExpenseReader::read() proposed.
     *
     * A total in another currency is not put in the amount: the amount is what left the bank in
     * the home currency, which the document cannot say. The figure goes in the notes instead and
     * comes back as 'foreign', for the screen to say so. When the vendor has been paid before,
     * that expense's category, assignment and "paid from" are carried over and its id comes back
     * as 'history'.
     *
     * @return array{data: array, foreign: ?string, history: int}
     */
    public static function prefill(array $draft, string $token): array
    {
        $amount   = $draft['amount'] ?? null;
        $currency = (string) ($draft['currency'] ?? '');
        $foreign  = $amount !== null && $currency !== '' && $currency !== self::HOME_CURRENCY
            ? number_format((float) $amount, 2, '.', '') . ' ' . $currency
            : null;
        $notes    = trim((string) ($draft['notes'] ?? ''));

        if ($foreign !== null) {
            $notes = trim('Invoiced ' . $foreign . '. ' . $notes);
        }

        $data = [
            'merchant'       => (string) ($draft['merchant'] ?? ''),
            'item'           => (string) ($draft['item'] ?? ''),
            'category'       => (string) ($draft['category'] ?? ''),
            'amount'         => $amount !== null && $foreign === null ? number_format((float) $amount, 2, '.', '') : '',
            'expense_date'   => (string) ($draft['date'] ?? '') ?: self::today(),
            'transaction_id' => (string) ($draft['reference'] ?? ''),
            'description'    => $notes,
            'receipt_import' => $token,
        ];

        $last = self::lastFrom($data['merchant']);

        if ($last) {
            $data['category']        = (string) ($last->category ?: $data['category']);
            $data['client_id']       = $last->client_id ?: '';
            $data['account_id']      = $last->account_id ?: '';
            $data['project_id']      = $last->project_id ?: '';
            $data['billable']        = (int) $last->billable;
            $data['payment_account'] = (string) $last->payment_account;
        }

        return ['data' => $data, 'foreign' => $foreign, 'history' => $last ? (int) $last->id : 0];
    }

    /** Today's date where the administrator is. See ExpenseTable::today(). */
    public static function today(): string
    {
        return ExpenseTable::today();
    }

    /** The most recent expense paid to this vendor, or null. */
    public static function lastFrom(string $merchant): ?object
    {
        $merchant = mb_strtolower(trim($merchant));

        if ($merchant === '') {
            return null;
        }

        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_expenses'))
            ->where('LOWER(' . $db->quoteName('merchant') . ') = :merchant')
            ->order($db->quoteName('expense_date') . ' DESC, ' . $db->quoteName('id') . ' DESC')
            ->bind(':merchant', $merchant);

        return $db->setQuery($query, 0, 1)->loadObject() ?: null;
    }

    /**
     * Expenses that look like the purchase a draft describes: the same vendor with the same
     * reference, or with the same amount within a few days. An expense already typed in by hand
     * is found this way, before the invoice for it is entered a second time.
     *
     * @return object[]  id, item, amount, expense_date
     */
    public static function similar(array $draft): array
    {
        $merchant  = mb_strtolower(trim((string) ($draft['merchant'] ?? '')));
        $reference = trim((string) ($draft['reference'] ?? ''));
        $amount    = $draft['amount'] ?? null;
        $date      = (string) ($draft['date'] ?? '');

        if ($merchant === '') {
            return [];
        }

        $db    = self::db();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'item', 'amount', 'expense_date']))
            ->from($db->quoteName('#__mothership_expenses'))
            ->where('LOWER(' . $db->quoteName('merchant') . ') = :merchant')
            ->order($db->quoteName('id') . ' DESC')
            ->bind(':merchant', $merchant);

        $either = [];

        if ($reference !== '') {
            $either[] = $db->quoteName('transaction_id') . ' = :reference';
            $query->bind(':reference', $reference);
        }

        if ($amount !== null && $date !== '') {
            $total = number_format((float) $amount, 2, '.', '');
            $days  = self::SIMILAR_DAYS;
            $either[] = '(' . $db->quoteName('amount') . ' = :amount AND ABS(DATEDIFF(' . $db->quoteName('expense_date') . ', :date)) <= :days)';
            $query->bind(':amount', $total)
                ->bind(':date', $date)
                ->bind(':days', $days, ParameterType::INTEGER);
        }

        if (!$either) {
            return [];
        }

        return $db->setQuery($query->where('(' . implode(' OR ', $either) . ')'), 0, 5)->loadObjectList() ?: [];
    }

    /**
     * After the expense is saved: move the parked file in with the receipts and record it against
     * the expense. False when the upload has gone or the file could not be kept.
     */
    public static function attach(int $expenseId, string $token, int $userId): bool
    {
        $parked = self::pending($token);

        if ($expenseId <= 0 || $parked === null) {
            return false;
        }

        $base = self::storePath();
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        $stored = sha1_file($parked->path) . '.' . ExpenseReader::TYPES[$parked->mime];
        $size   = (int) filesize($parked->path);

        // The same file may already be here, left by an expense since deleted.
        if (!@rename($parked->path, $base . '/' . $stored)) {
            return false;
        }

        @unlink(self::storePath() . '/pending/' . $token . '.json');

        $row = (object) [
            'context'       => self::CONTEXT,
            'resource_id'   => $expenseId,
            'filename'      => $stored,
            'original_name' => mb_substr($parked->name, 0, 255),
            'mime'          => $parked->mime,
            'size'          => $size,
            'uploaded_by'   => $userId,
            'created'       => Factory::getDate()->toSql(),
        ];

        return self::db()->insertObject('#__mothership_attachments', $row, 'id');
    }

    /**
     * The receipts on an expense, oldest first, each with its path (null when the file is gone).
     *
     * @return object[]
     */
    public static function forExpense(int $expenseId): array
    {
        if ($expenseId <= 0) {
            return [];
        }

        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_attachments'))
            ->where($db->quoteName('context') . ' = ' . $db->quote(self::CONTEXT))
            ->where($db->quoteName('resource_id') . ' = :id')
            ->order($db->quoteName('id') . ' ASC')
            ->bind(':id', $expenseId, ParameterType::INTEGER);

        return array_map([self::class, 'locate'], $db->setQuery($query)->loadObjectList() ?: []);
    }

    /** One receipt by its attachment id, with its path, or null. */
    public static function find(int $attachmentId): ?object
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $db    = self::db();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_attachments'))
            ->where($db->quoteName('context') . ' = ' . $db->quote(self::CONTEXT))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $attachmentId, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        return $row ? self::locate($row) : null;
    }

    /** An expense is being deleted: its receipts go with it, unless another expense has the file. */
    public static function deleteFor(int $expenseId): void
    {
        $db = self::db();

        foreach (self::forExpense($expenseId) as $receipt) {
            $id = (int) $receipt->id;

            $db->setQuery(
                $db->getQuery(true)->delete($db->quoteName('#__mothership_attachments'))
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER)
            )->execute();

            $name   = (string) $receipt->filename;
            $shared = (int) $db->setQuery(
                $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__mothership_attachments'))
                    ->where($db->quoteName('context') . ' = ' . $db->quote(self::CONTEXT))
                    ->where($db->quoteName('filename') . ' = :name')
                    ->bind(':name', $name)
            )->loadResult();

            if (!$shared && $receipt->path) {
                @unlink($receipt->path);
            }
        }
    }

    private static function locate(object $row): object
    {
        $path      = self::storePath() . '/' . basename((string) $row->filename);
        $row->path = is_file($path) ? $path : null;

        return $row;
    }

    /** The pending directory, made if needed, with uploads nobody saved cleared out. */
    private static function pendingDir(): string
    {
        $dir = self::storePath() . '/pending';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        foreach (array_merge(glob($dir . '/*.bin') ?: [], glob($dir . '/*.json') ?: []) as $old) {
            if (filemtime($old) < time() - self::PENDING_TTL) {
                @unlink($old);
            }
        }

        return $dir;
    }

    /** The name the file is shown and downloaded under: the user's, tidied, with the true extension. */
    private static function cleanName(string $name, string $ext): string
    {
        $stem = pathinfo(basename(str_replace('\\', '/', $name)), PATHINFO_FILENAME);
        $stem = trim(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '', $stem) ?? '');

        return mb_substr($stem !== '' ? $stem : 'receipt', 0, 200) . '.' . $ext;
    }
}
