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
    /**
     * Logs an action to the database.
     *
     * This method inserts a log entry into the `#__mothership_logs` table with the provided parameters.
     *
     * @param array $params An associative array containing the following keys:
     *     - client_id (int|null): The ID of the client. Defaults to NULL if not provided.
     *     - account_id (int|null): The ID of the account. Defaults to NULL if not provided.
     *     - object_type (string|null): The type of the object being logged. Defaults to NULL if not provided.
     *     - object_id (int|null): The ID of the object being logged. Defaults to NULL if not provided.
     *     - action (string|null): The action being logged. Defaults to NULL if not provided.
     *     - meta (array): Additional metadata for the log entry. Defaults to an empty array if not provided.
     *     - user_id (int|null): The ID of the user performing the action. Defaults to the current user's ID if not provided.
     *
     * @return bool Returns true on successful execution of the query, or false on failure.
     */
    public static function log(array $params): bool
    {
        $user = Factory::getUser();
        $user_id = $user->id;

        try {
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
                isset($params['user_id']) ? (int) $params['user_id'] : $user_id,
                $db->quote(date('Y-m-d H:i:s')),
            ];

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__mothership_logs'))
                ->columns($columns)
                ->values(implode(',', $values));

            $db->setQuery($query);
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
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

    /**
     * Logs the completion of a payment.
     *
     * This method records a log entry when a payment's status changes to "Completed".
     *
     * @param object $payment The payment object containing details about the payment.
     *                        Expected properties:
     *                        - invoice_id (int|null): The ID of the associated invoice.
     *                        - id (int|null): The ID of the payment.
     *                        - client_id (int|null): The ID of the client making the payment.
     *                        - account_id (int|null): The ID of the account associated with the payment.
     *                        - amount (float|null): The total amount of the payment.
     *                        - payment_method (string|null): The method used for the payment.
     *
     * @return void
     */
    public static function logPaymentCompleted($payment): void
    {
        $invoiceId = $payment->invoice_id ?? 0;
        $paymentId = $payment->id ?? 0;
        $clientId = $payment->client_id ?? null;
        $accountId = $payment->account_id ?? null;
        $invoiceTotal = $payment->amount ?? 0.0;
        $paymentMethod = $payment->payment_method ?? '';
        $user = Factory::getUser();
        $userId = $user->id;
        
        self::log([
            'client_id' => $clientId,
            'account_id' => $accountId,
            'object_type' => 'payment',
            'object_id' => $paymentId,
            'action' => 'payment_status_changed',
            'meta' =>[
                'old_status' => 'Pending',
                'new_status' => 'Completed',
            ],
            'user_id' => $userId,
        ]);
    }

    public static function logPaymentFailed($paymentId, ?string $reason = null): void
    {
        self::logPaymentLifecycle('failed', 0, $paymentId, null, null, 0.0, '', $reason);
    }

    public static function logObjectViewed($object_type, $object_id, $client_id, $account_id): bool
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $username = $user->name ?: $user->username;

        if(self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'action' => 'viewed',
            'meta' =>[],
            'user_id' => $userId,
        ])) {
            return true;
        }
        return false;
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
        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'invoice',
            'object_id' => $invoice_id,
            'action' => 'status_opened',
            'meta' => [],
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

    /**
     * Logs the action of a domain being scanned.
     *
     * @param int $domain_id The ID of the domain that was scanned.
     * @param int $client_id The ID of the client associated with the domain.
     * @param int|null $account_id The ID of the account associated with the domain, or NULL if not applicable.
     *
     * @return void
     */
    public static function logDomainScanned($domain_id, $client_id, $account_id=NULL): void
    {
        $logArray = [
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'domain',
            'object_id' => $domain_id,
            'action' => 'scanned',
            'meta' => [],
        ];

        self::log($logArray);
    }

    /**
     * Logs the action of a project being scanned.
     *
     * @param int $project_id The ID of the project that was scanned.
     * @param int $client_id The ID of the client associated with the project.
     * @param int $account_id The ID of the account associated with the project.
     *
     * @return void
     */
    public static function logProjectScanned($project_id, $client_id, $account_id): void
    {
        self::log([
            'client_id' => $client_id,
            'account_id' => $account_id,
            'object_type' => 'project',
            'object_id' => $project_id,
            'action' => 'scanned',
            'meta' => [],
        ]);
    }
}
