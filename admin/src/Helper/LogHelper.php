<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Mothership Log component helper.
 *
 * @since  1.6
 */
class LogHelper extends ContentHelper
{
    public static function log(array $params): bool
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_logs'))
            ->columns([
                'client_id',
                'account_id',
                'object_type',
                'object_id',
                'action',
                'meta',
                'description',
                'details',
                'user_id',
                'created'
            ])
            ->values(implode(',', [
                $db->quote($params['client_id'] ?? null),
                $db->quote($params['account_id'] ?? null),
                $db->quote($params['object_type'] ?? null),
                $db->quote($params['object_id'] ?? null),
                $db->quote($params['action'] ?? null),
                $db->quote(json_encode($params['meta'] ?? [])),
                $db->quote($params['description'] ?? null),
                $db->quote($params['details'] ?? null),
                $db->quote($params['user_id'] ?? Factory::getUser()->id),
                $db->quote(date('Y-m-d H:i:s')),
            ]));

        $db->setQuery($query);
        return $db->execute();
    }

    public static function logPaymentLifecycle(
        string $event,
        int $invoiceId,
        int $paymentId,
        ?int $clientId = null,
        ?int $accountId = null,
        float $amount = 0.0,
        string $method = '',
        ?string $extraDetails = null
    ): void {
        $eventLabels = [
            'initiated' => 'initiated',
            'completed' => 'completed',
            'failed' => 'failed',
            'refunded' => 'refunded',
        ];

        $description = "Payment {$eventLabels[$event]} for Invoice #" . str_pad($invoiceId, 4, '0', STR_PAD_LEFT);
        $details = match ($event) {
            'initiated', 'completed' => "A payment of \${$amount} was {$eventLabels[$event]} for Invoice #" . str_pad($invoiceId, 4, '0', STR_PAD_LEFT) . " using {$method}.",
            'failed' => "Payment ID {$paymentId} failed. " . $extraDetails,
            'refunded' => "Payment ID {$paymentId} was refunded. " . $extraDetails,
            default => "Payment event '{$event}' occurred for Payment ID {$paymentId}."
        };

        self::log([
            'object_type' => 'payment',
            'object_id' => $paymentId,
            'client_id' => $clientId,
            'account_id' => $accountId,
            'action' => $event,
            'description' => $description,
            'details' => $details,
            'meta' => json_encode([]),
            'user_id' => Factory::getUser()->id,
        ]);
    }

    public static function logPaymentInitiated($invoiceId, $paymentId, $clientId, $accountId, $invoiceTotal, $paymentMethod): void
    {
        self::logPaymentLifecycle('initiated', $invoiceId, $paymentId, $clientId, $accountId, $invoiceTotal, $paymentMethod);
    }

    public static function logPaymentCompleted($invoiceId, $paymentId, $clientId, $accountId, $invoiceTotal, $paymentMethod): void
    {
        self::logPaymentLifecycle('completed', $invoiceId, $paymentId, $clientId, $accountId, $invoiceTotal, $paymentMethod);
    }

    public static function logPaymentFailed($paymentId, ?string $reason = null): void
    {
        self::logPaymentLifecycle('failed', 0, $paymentId, null, null, 0.0, '', $reason);
    }

    public static function logPaymentViewed($client_id, $account_id, $payment_id): void
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'payment',
            'object_id' => $payment_id,
            'action' => 'viewed',
            'description' => "Payment viewed",
            'details' => "Payment ID {$payment_id } was viewed by `{$username}`.",
            'meta' => json_encode([]),
            'user_id' => $userId,
        ]);
    }

}
