<?php

namespace TrevorBice\Component\Mothership\Administrator\Service\Ai;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * What the AI features cost.
 *
 * Every provider call that reports tokens is written to #__mothership_ai_usage with the feature
 * that made it, the record it was about (and through that the client), the tokens and
 * a cost. The cost is an estimate: tokens times the connector's rate for the model at the time
 * of the call, kept on the row so a later price change does not rewrite history.
 *
 * It is a log for watching the spend, read on the AI Usage screen. It writes no expense rows:
 * the provider's bill is entered as an expense by hand.
 *
 * AiService does the recording; callers only say what a call is for, through the 'usage' option:
 *
 *   AiService::text($prompt, ['usage' => ['feature' => 'expense.read']]);
 */
class AiUsage
{
    public const TABLE = '#__mothership_ai_usage';

    /** The features that record usage today. Anything else is filed as "other". */
    public const FEATURES = ['expense.read', 'connection.test', 'other'];

    /** Where a resource's client, account and project are read from. */
    private const RESOURCE_TABLES = [
        'invoice' => '#__mothership_invoices',
    ];

    /**
     * The estimated cost of one call in USD, or null when there is no rate to price it with.
     *
     * @param array      $usage  ['input' => tokens, 'output' => tokens]
     * @param array|null $rate   ['input' => USD per million tokens, 'output' => USD per million tokens]
     */
    public static function cost(array $usage, ?array $rate): ?float
    {
        if ($rate === null || !isset($rate['input'], $rate['output'])) {
            return null;
        }

        $input  = max(0, (int) ($usage['input'] ?? 0));
        $output = max(0, (int) ($usage['output'] ?? 0));

        return round(($input * (float) $rate['input'] + $output * (float) $rate['output']) / 1000000, 6);
    }

    /**
     * The rate for a model: its own entry, else the longest listed id it starts with, so a dated
     * snapshot ("claude-haiku-4-5-20251001") is priced as its family.
     *
     * @param array $rates  ['model-id' => ['input' => float, 'output' => float], ...]
     */
    public static function rateFor(string $model, array $rates): ?array
    {
        $model = strtolower(trim($model));

        if ($model === '') {
            return null;
        }

        $best = null;

        foreach ($rates as $id => $rate) {
            $id = strtolower((string) $id);

            if ($id === $model) {
                return $rate;
            }

            if (str_starts_with($model, $id . '-') && ($best === null || \strlen($id) > \strlen($best))) {
                $best = $id;
            }
        }

        return $best !== null ? $rates[$best] : null;
    }

    /**
     * Record one provider call. Returns the new row's id, or 0 when nothing was written: the
     * provider reported no tokens, or the write failed. It never throws, because metering must
     * not be able to break the feature it is metering.
     *
     * @param string   $provider  The connector's key, e.g. "claude".
     * @param AiResult $result    What the connector returned.
     * @param array    $context   feature, resource_type, resource_id, client_id, account_id, project_id.
     * @param array    $rates     The connector's rates, see rateFor().
     */
    public static function record(string $provider, AiResult $result, array $context = [], array $rates = []): int
    {
        $input  = max(0, (int) ($result->usage['input'] ?? 0));
        $output = max(0, (int) ($result->usage['output'] ?? 0));

        if ($input === 0 && $output === 0) {
            return 0;
        }

        try {
            $feature = (string) ($context['feature'] ?? '');
            $feature = \in_array($feature, self::FEATURES, true) ? $feature : 'other';
            $type    = (string) ($context['resource_type'] ?? '');
            $id      = (int) ($context['resource_id'] ?? 0);
            $model   = (string) ($result->model ?? '');

            $row = (object) [
                'provider'      => strtolower($provider),
                'model'         => $model !== '' ? $model : null,
                'feature'       => $feature,
                'resource_type' => $type !== '' && $id > 0 ? $type : null,
                'resource_id'   => $type !== '' && $id > 0 ? $id : null,
                'client_id'     => self::id($context['client_id'] ?? null),
                'account_id'    => self::id($context['account_id'] ?? null),
                'project_id'    => self::id($context['project_id'] ?? null),
                'input_tokens'  => $input,
                'output_tokens' => $output,
                'cost'          => self::cost(['input' => $input, 'output' => $output], self::rateFor($model, $rates)),
                'created'       => Factory::getDate()->toSql(),
                'created_by'    => (int) (Factory::getApplication()->getIdentity()->id ?? 0),
            ];

            self::attribute($row);

            self::db()->insertObject(self::TABLE, $row, 'id');

            return (int) $row->id;
        } catch (\Throwable $e) {
            Log::add('AI usage was not recorded: ' . $e->getMessage(), Log::WARNING, 'mothership-ai-usage');

            return 0;
        }
    }

    /**
     * Totals for the usage screen: this month so far, last month and everything.
     *
     * @return array<string, array{calls:int, input:int, output:int, cost:float}>
     */
    public static function summary(?string $asOf = null): array
    {
        $asOf      = $asOf ?: self::today();
        $thisMonth = substr($asOf, 0, 7);
        $lastMonth = (new \DateTimeImmutable($thisMonth . '-01'))->modify('-1 month')->format('Y-m');

        [$thisStart, $thisEnd] = self::monthBounds($thisMonth);
        [$lastStart]           = self::monthBounds($lastMonth);

        $db = self::db();

        return [
            'this_month' => self::sum($db->quoteName('created') . ' >= ' . $db->quote($thisStart) . ' AND ' . $db->quoteName('created') . ' < ' . $db->quote($thisEnd)),
            'last_month' => self::sum($db->quoteName('created') . ' >= ' . $db->quote($lastStart) . ' AND ' . $db->quoteName('created') . ' < ' . $db->quote($thisStart)),
            'all_time'   => self::sum('1 = 1'),
        ];
    }

    /**
     * A feature's label, e.g. "Connection test".
     */
    public static function featureLabel(string $feature): string
    {
        self::loadLanguage();

        $key = 'COM_MOTHERSHIP_AIUSAGE_FEATURE_' . strtoupper(str_replace('.', '_', $feature));

        return Factory::getApplication()->getLanguage()->hasKey($key) ? Text::_($key) : $feature;
    }

    /**
     * Today's date in the site's configured timezone, so a month turns at local midnight.
     */
    public static function today(): string
    {
        return (new \DateTimeImmutable('now', self::timezone()))->format('Y-m-d');
    }

    /**
     * Fill in the client, account and project a call belongs to from the record it was about,
     * where the caller did not say.
     */
    private static function attribute(object $row): void
    {
        $table = self::RESOURCE_TABLES[$row->resource_type ?? ''] ?? null;

        if ($table === null || ($row->client_id !== null && $row->account_id !== null && $row->project_id !== null)) {
            return;
        }

        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['client_id', 'account_id', 'project_id']))
                ->from($db->quoteName($table))
                ->where($db->quoteName('id') . ' = ' . (int) $row->resource_id)
        );

        if ($found = $db->loadObject()) {
            foreach (['client_id', 'account_id', 'project_id'] as $field) {
                $row->$field ??= self::id($found->$field);
            }
        }
    }

    /**
     * @return array{calls:int, input:int, output:int, cost:float}
     */
    private static function sum(string $where): array
    {
        $db = self::db();
        $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*) AS calls, COALESCE(SUM(input_tokens), 0) AS input, COALESCE(SUM(output_tokens), 0) AS output, COALESCE(SUM(cost), 0) AS cost')
                ->from($db->quoteName(self::TABLE))
                ->where($where)
        );
        $row = $db->loadObject();

        return [
            'calls'  => (int) ($row->calls ?? 0),
            'input'  => (int) ($row->input ?? 0),
            'output' => (int) ($row->output ?? 0),
            'cost'   => (float) ($row->cost ?? 0),
        ];
    }

    /**
     * A month's first instant and the next month's, as UTC datetimes: rows are stored in UTC and
     * a month is the site's local month.
     *
     * @param string $month  Y-m
     *
     * @return array{0:string, 1:string}
     */
    private static function monthBounds(string $month): array
    {
        $utc   = new \DateTimeZone('UTC');
        $start = new \DateTimeImmutable($month . '-01 00:00:00', self::timezone());

        return [
            $start->setTimezone($utc)->format('Y-m-d H:i:s'),
            $start->modify('+1 month')->setTimezone($utc)->format('Y-m-d H:i:s'),
        ];
    }

    private static function timezone(): \DateTimeZone
    {
        return new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC') ?: 'UTC');
    }

    private static function id($value): ?int
    {
        return (int) $value > 0 ? (int) $value : null;
    }

    /**
     * The labels live in the component's admin language file.
     */
    private static function loadLanguage(): void
    {
        static $loaded = false;

        if (!$loaded) {
            Factory::getApplication()->getLanguage()->load('com_mothership', JPATH_ADMINISTRATOR);
            $loaded = true;
        }
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
