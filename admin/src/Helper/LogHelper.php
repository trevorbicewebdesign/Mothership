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
use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;

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
            'meta' => json_encode([]),
            'user_id' => $userId,
        ]);
    }

    /**
     * Log a payment status change.
     *
     * @param object $payment     The payment object.
     * @param string $newStatus   The new status (e.g., 'completed').
     *
     * @return void
     */
    public static function logStatusChange(object $payment, string $newStatus): void
    {
        $oldStatus = $payment->status ?? null;
        if ($oldStatus === $newStatus) {
            // Don't log if there's no actual change
            return;
        }

        $client_id = $payment->client_id ?? null;
        $account_id = $payment->account_id ?? null;
        $object_id = $payment->id ?? null;

        $user = Factory::getApplication()->getIdentity();
        $user_display_name = $user->name ?: $user->username;

        $meta = [
            'old_status' => PaymentHelper::getStatus($oldStatus),
            'new_status' => PaymentHelper::getStatus($newStatus),
        ];

        $logEntry = [
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'payment',
            'object_id' => $object_id,
            'action'=> 'payment_status_changed',
            'meta' => json_encode($meta),
            'user_id' => $user->id,
            'created' => date('Y-m-d H:i:s'),
        ];

        
        try{
            self::log($logEntry);
        }
        catch (\Exception $e) {
            // Handle logging error (e.g., log to a file, send an email, etc.)
            Factory::getApplication()->enqueueMessage(Text::_('COM_MOTHERSHIP_LOGGING_ERROR'), 'error');
        }
    }

}
