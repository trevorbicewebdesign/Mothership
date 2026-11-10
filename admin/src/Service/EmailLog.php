<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The record of every email the component tries to send: what, to whom, about which record, and
 * whether it left. EmailService writes it; the Emails screen and the Email tab on invoices,
 * payments and proposals read it.
 *
 * Writing the log must never stop an email going out, so every write swallows its own errors and
 * reports them to the Joomla log instead.
 */
class EmailLog
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [self::STATUS_SENT, self::STATUS_FAILED, self::STATUS_SKIPPED, self::STATUS_PENDING];

    /**
     * Record that a send is about to be attempted. Returns the new row's id, or 0 when the log
     * itself could not be written.
     *
     * @param array $fields recipient and subject, plus any of client_id, account_id, object_type,
     *                      object_id, template, cc, bcc, reply_to, body_html, message_id, resend_of.
     */
    public static function start(array $fields): int
    {
        $row = (object) [
            'client_id'   => self::idOrNull($fields['client_id'] ?? null),
            'account_id'  => self::idOrNull($fields['account_id'] ?? null),
            'object_type' => self::textOrNull($fields['object_type'] ?? null, 50),
            'object_id'   => self::idOrNull($fields['object_id'] ?? null),
            'template'    => self::textOrNull($fields['template'] ?? null, 100),
            'recipient'   => mb_substr((string) ($fields['recipient'] ?? ''), 0, 255),
            'cc'          => self::textOrNull($fields['cc'] ?? null, 255),
            'bcc'         => self::textOrNull($fields['bcc'] ?? null, 255),
            'reply_to'    => self::textOrNull($fields['reply_to'] ?? null, 255),
            'subject'     => mb_substr((string) ($fields['subject'] ?? ''), 0, 255),
            'body_html'   => $fields['body_html'] ?? null,
            'message_id'  => self::textOrNull($fields['message_id'] ?? null, 255),
            'status'      => self::STATUS_PENDING,
            'error'       => null,
            'resend_of'   => self::idOrNull($fields['resend_of'] ?? null),
            'user_id'     => self::currentUserId(),
            'created'     => Factory::getDate()->toSql(),
            'sent_at'     => null,
        ];

        try {
            self::db()->insertObject('#__mothership_emails', $row, 'id');

            return (int) $row->id;
        } catch (\Throwable $e) {
            Log::add('Email log could not be written: ' . $e->getMessage(), Log::ERROR, 'com_mothership');

            return 0;
        }
    }

    /** The mail server accepted the message. */
    public static function sent(int $id, ?string $messageId = null): void
    {
        $fields = ['status' => self::STATUS_SENT, 'sent_at' => Factory::getDate()->toSql(), 'error' => null];

        if ($messageId !== null && $messageId !== '') {
            $fields['message_id'] = mb_substr($messageId, 0, 255);
        }

        self::finish($id, $fields);
    }

    /** The message did not go. $error is kept so the screen can say why. */
    public static function failed(int $id, string $error): void
    {
        self::finish($id, ['status' => self::STATUS_FAILED, 'error' => $error]);
    }

    /** The message was deliberately not sent. $reason says who decided and why. */
    public static function skipped(int $id, string $reason): void
    {
        self::finish($id, ['status' => self::STATUS_SKIPPED, 'error' => $reason]);
    }

    private static function finish(int $id, array $fields): void
    {
        if ($id <= 0) {
            return;
        }

        $row     = (object) $fields;
        $row->id = $id;

        try {
            self::db()->updateObject('#__mothership_emails', $row, 'id', true);
        } catch (\Throwable $e) {
            Log::add('Email log could not be updated: ' . $e->getMessage(), Log::ERROR, 'com_mothership');
        }
    }

    /** One entry with the names the screens show, or null. */
    public static function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db    = self::db();
        $query = self::describedQuery($db)->where($db->quoteName('e.id') . ' = ' . $id);

        return $db->setQuery($query)->loadObject() ?: null;
    }

    /**
     * Every email about one record, newest first. $type is invoice, payment or proposal.
     */
    public static function forObject(string $type, int $id): array
    {
        if ($type === '' || $id <= 0) {
            return [];
        }

        $db    = self::db();
        $query = self::describedQuery($db)
            ->where($db->quoteName('e.object_type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('e.object_id') . ' = ' . $id)
            ->order($db->quoteName('e.created') . ' DESC, ' . $db->quoteName('e.id') . ' DESC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /** How many sends have failed, for a warning somewhere prominent. */
    public static function failedCount(): int
    {
        $db = self::db();

        try {
            return (int) $db->setQuery(
                'SELECT COUNT(*) FROM ' . $db->quoteName('#__mothership_emails')
                . ' WHERE ' . $db->quoteName('status') . ' = ' . $db->quote(self::STATUS_FAILED)
            )->loadResult();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * The base query for one or more entries: the row plus the client, account and user names and
     * a label for what it was about. Shared with the list model so every screen describes an
     * entry the same way.
     */
    public static function describedQuery(DatabaseInterface $db)
    {
        return $db->getQuery(true)
            ->select([
                $db->quoteName('e') . '.*',
                $db->quoteName('c.name', 'client_name'),
                $db->quoteName('a.name', 'account_name'),
                $db->quoteName('u.name', 'user_name'),
                $db->quoteName('u.username', 'user_username'),
                $db->quoteName('oi.number', 'invoice_number'),
                $db->quoteName('op.number', 'proposal_number'),
            ])
            ->from($db->quoteName('#__mothership_emails', 'e'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = e.client_id')
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON a.id = e.account_id')
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = e.user_id')
            ->join('LEFT', $db->quoteName('#__mothership_invoices', 'oi') . ' ON e.object_type = ' . $db->quote('invoice') . ' AND oi.id = e.object_id')
            ->join('LEFT', $db->quoteName('#__mothership_proposals', 'op') . ' ON e.object_type = ' . $db->quote('proposal') . ' AND op.id = e.object_id');
    }

    /** "Invoice #1312", "Proposal #40", "Payment #88". Empty when it was about nothing. */
    public static function aboutLabel(object $item): string
    {
        $id = (int) ($item->object_id ?? 0);

        switch ((string) ($item->object_type ?? '')) {
            case 'invoice':
                return Text::sprintf('COM_MOTHERSHIP_EMAIL_ABOUT_INVOICE', $item->invoice_number !== null && $item->invoice_number !== '' ? $item->invoice_number : $id);
            case 'proposal':
                return Text::sprintf('COM_MOTHERSHIP_EMAIL_ABOUT_PROPOSAL', $item->proposal_number !== null && $item->proposal_number !== '' ? $item->proposal_number : $id);
            case 'payment':
                return Text::sprintf('COM_MOTHERSHIP_EMAIL_ABOUT_PAYMENT', $id);
        }

        return '';
    }

    /** The admin screen for what an entry was about, or empty when there is none. */
    public static function aboutLink(object $item): string
    {
        $id = (int) ($item->object_id ?? 0);

        if ($id <= 0) {
            return '';
        }

        switch ((string) ($item->object_type ?? '')) {
            case 'invoice':
            case 'proposal':
            case 'payment':
                return 'index.php?option=com_mothership&task=' . $item->object_type . '.edit&id=' . $id;
        }

        return '';
    }

    public static function statusLabel(string $status): string
    {
        return Text::_('COM_MOTHERSHIP_EMAIL_STATUS_' . strtoupper($status ?: self::STATUS_PENDING));
    }

    /** The coloured status pill the list, the detail and the tabs all use. */
    public static function pill(string $status): string
    {
        $classes = [
            self::STATUS_SENT    => 'bg-success',
            self::STATUS_FAILED  => 'bg-danger',
            self::STATUS_SKIPPED => 'bg-secondary',
            self::STATUS_PENDING => 'bg-warning text-dark',
        ];
        $status = $status ?: self::STATUS_PENDING;

        return '<span class="badge ' . ($classes[$status] ?? 'bg-secondary') . '">'
            . htmlspecialchars(self::statusLabel($status), ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private static function currentUserId(): ?int
    {
        try {
            $app  = Factory::getApplication();
            $user = $app ? $app->getIdentity() : null;
        } catch (\Throwable $e) {
            return null;
        }

        return ($user && !$user->guest && (int) $user->id > 0) ? (int) $user->id : null;
    }

    private static function idOrNull($value): ?int
    {
        return ($value === null || $value === '' || (int) $value === 0) ? null : (int) $value;
    }

    private static function textOrNull($value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
