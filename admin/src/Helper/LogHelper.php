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
        $columns = [
            'client_id',
            'account_id',
            'object_type',
            'object_id',
            'action',
            'meta',
            'user_id',
            'created'
        ];

        $values = [
            isset($params['client_id']) ? (int) $params['client_id'] : 'NULL',
            isset($params['account_id']) ? (int) $params['account_id'] : 'NULL',
            $db->quote($params['object_type'] ?? null),
            isset($params['object_id']) ? (int) $params['object_id'] : 'NULL',
            $db->quote($params['action'] ?? null),
            $db->quote(json_encode($params['meta'] ?? [])),
            isset($params['user_id']) ? (int) $params['user_id'] : (int) Factory::getUser()->id,
            $db->quote(date('Y-m-d H:i:s')),
        ];

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_logs'))
            ->columns($columns)
            ->values(implode(',', $values));

        $db->setQuery($query);
        return $db->execute();
    }


    public static function logPaymentLifecycle(string $event, int $invoiceId, int $paymentId, ?int $clientId = null, ?int $accountId = null, float $amount = 0.0, string $method = '', ?string $extraDetails = null): void {
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

        $criteria = [
            'object_type' => 'payment',
            'object_id' => $paymentId,
            'client_id' => $clientId,
            'account_id' => $accountId,
            'action' => $event,
            //'meta' => [],
            //'user_id' => Factory::getUser()->id,
        ];

        self::log($criteria);
    }

    public static function logPaymentInitiated($invoice_id, $payment_id, $client_id, $account_id, $invoiceTotal, $paymentMethod): void
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'payment',
            'object_id' => $payment_id,
            'action' => 'initiated',
            'meta' =>[
                'invoice_id' => $invoice_id,
                'payment_method' => $paymentMethod,
                'amount' => $invoiceTotal,
            ],
            'user_id' => $userId,
        ]);
    }

    public static function logPaymentCompleted($payment): void
    {
        $invoiceId = $payment->invoice_id ?? 0;
        $paymentId = $payment->id ?? 0;
        $clientId = $payment->client_id ?? null;
        $accountId = $payment->account_id ?? null;
        $invoiceTotal = $payment->amount ?? 0.0;
        $paymentMethod = $payment->payment_method ?? '';
        
        self::logPaymentLifecycle('completed', $invoiceId, $paymentId, $clientId, $accountId, $invoiceTotal, $paymentMethod);
    }

    public static function logPaymentFailed($paymentId, ?string $reason = null): void
    {
        self::logPaymentLifecycle('failed', 0, $paymentId, null, null, 0.0, '', $reason);
    }

    public static function logObjectViewed($object_type, $object_id, $client_id, $account_id): void
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'action' => 'viewed',
            'meta' =>[],
            'user_id' => $userId,
        ]);
    }

    public static function logDomainViewed($client_id, $account_id, $domain_id): void
    {
       self::logObjectViewed('domain', $domain_id, $client_id, $account_id);
    }

    public static function logProjectViewed($client_id, $account_id, $project_id): void
    {
        self::logObjectViewed('project', $project_id, $client_id, $account_id);
    }

    public static function logPaymentViewed($client_id, $account_id, $payment_id): void
    {
        self::logObjectViewed( 'payment', $payment_id, $client_id, $account_id);
    }

    public static function logInvoiceViewed($client_id, $account_id, $invoice_id): void
    {
        self::logObjectViewed( 'invoice', $invoice_id, $client_id, $account_id);
    }

    public static function logAccountViewed($client_id, $account_id): void
    {
        self::logObjectViewed( 'account', $account_id, $client_id, $account_id);
    }

    public static function logInvoiceStatusOpened($invoice_id, $client_id, $account_id): void
    {
        $user = Factory::getApplication()->getIdentity();
        $user_display_name = $user->name ?: $user->username;

        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'invoice',
            'object_id' => $invoice_id,
            'action' => 'status_opened',
            'meta' => [],
            'user_id' => $user->id,
            'created' => date('Y-m-d H:i:s'),
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
            'meta' => $meta,
            'created' => date('Y-m-d H:i:s'),
        ];

        
        try{
            self::log($logEntry);
        }
        catch (\Exception $e) {
            // Handle logging error (e.g., log to a file, send an email, etc.)
            Factory::getApplication()->enqueueMessage(sprintf(Text::_('COM_MOTHERSHIP_LOGGING_ERROR'), $e->getMessage()), 'error');
        }
    }

    public static function logDomainScanned($domain_id, $client_id, $account_id=NULL): void
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        $logArray = [
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'domain',
            'object_id' => $domain_id,
            'action' => 'scanned',
            'meta' => [],
            'user_id' => $userId,
        ];

        self::log($logArray);
    }

    public static function logProjectScanned($project_id, $client_id, $account_id): void
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'project',
            'object_id' => $project_id,
            'action' => 'scanned',
            'meta' => [],
            'user_id' => $userId,
        ]);
    }
}
