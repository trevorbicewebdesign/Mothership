<?php
/**
 * Payment Helper for Mothership Payment Plugins
 *
 * Provides methods to update an invoice record, insert payment data, 
 * and allocate the payment to the corresponding invoice.
 *
 * @package     Mothership
 * @subpackage  Helper
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;

class PaymentHelper
{

    public static function updatePaymentStatus($paymentId, $status)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('status') . ' = ' . (int) $status)
            ->where($db->quoteName('id') . ' = ' . (int) $paymentId);
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            Log::add("Failed to update payment ID $paymentId: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }
    }

    public static function setInvoicePaid($invoiceId)
    {
        self::updateInvoiceStatus($invoiceId, 3);
    }

    /**
     * Updates the invoice status and paid date.
     *
     * @param   int     $invoiceId  The invoice ID.
     * @param   int     $status     The new status (e.g., 3 for paid).
     * @param   string  $paidDate   The paid date in Y-m-d H:i:s format.
     *
     * @return  bool  True on success, false on failure.
     */
    public static function updateInvoiceStatus($invoiceId, $status)
    {
        $paidDate = date('Y-m-d H:i:s');
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_invoices'))
            ->set($db->quoteName('status') . ' = ' . (int) $status)
            ->set($db->quoteName('paid_date') . ' = ' . $db->quote($paidDate))
            ->where($db->quoteName('id') . ' = ' . (int) $invoiceId);
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            Log::add("Failed to update invoice ID $invoiceId: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }
    }

    /**
     * Inserts a payment record.
     *
     * @param   int     $clientId       The client ID.
     * @param   int     $accountId      The account ID.
     * @param   float   $amount         The payment amount.
     * @param   string  $paymentDate    The payment date.
     * @param   float   $fee            The fee amount.
     * @param   int     $feePassedOn    Whether the fee is passed on.
     * @param   string  $paymentMethod  The payment method.
     * @param   string  $txnId          The transaction ID.
     * @param   int     $status         The payment status.
     *
     * @return  int|false  The new payment ID on success, or false on failure.
     */
    public static function insertPaymentRecord(int $clientId, int $accountId, float $amount, $paymentDate, float $fee, $feePassedOn, $paymentMethod, $txnId, int $status)
    {

        try{
            ClientHelper::getClient($clientId);
        }
        catch(\Exception $e){
            // error message should bubble up
            throw new \RuntimeException($e->getMessage());
        }

        // must have valid account ID
        try{
            AccountHelper::getAccount($accountId);
        }
        catch(\Exception $e){
            // error message should bubble up
            throw new \RuntimeException($e->getMessage());
        }

        // must have a valid amount
        if( empty($amount) || $amount <= 0 ){
            throw new \RuntimeException("Invalid amount");
        }

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $columns = [
            $db->quoteName('client_id'),
            $db->quoteName('account_id'),
            $db->quoteName('amount'),
            $db->quoteName('payment_date'),
            $db->quoteName('fee_amount'),
            $db->quoteName('fee_passed_on'),
            $db->quoteName('payment_method'),
            $db->quoteName('transaction_id'),
            $db->quoteName('status'),
            $db->quotename('processed_date')
        ];
        $values = [
            (string) (int) $clientId,
            (string) (int) $accountId,
            (string) (float) $amount,
            $db->quote($paymentDate),
            (string) (float) $fee,
            (string) (int) $feePassedOn,
            $db->quote($paymentMethod),
            $db->quote($txnId),
            (string) (int) $status,
            $db->quote(date('Y-m-d H:i:s'))
        ];
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_payments'))
            ->columns(implode(', ', $columns))
            ->values(implode(', ', $values));
        $db->setQuery($query);
        

        try {
            $db->execute();
            return $db->insertid();
        } catch (\Exception $e) {
            Log::add("Failed to insert payment record: " . $e->getMessage(), Log::ERROR, 'payment');
            throw new \RuntimeException("Failed to insert payment record: " . $e->getMessage());
        }
    }

    /**
     * Allocates a payment to an invoice.
     *
     * @param   int     $paymentId  The payment record ID.
     * @param   int     $invoiceId  The invoice ID.
     * @param   float   $amount     The amount to apply.
     *
     * @return  bool  True on success, false on failure.
     */
    public static function allocatePayment($paymentId, $invoiceId, $amount)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $columns = [
            $db->quoteName('payment_id'),
            $db->quoteName('invoice_id'),
            $db->quoteName('applied_amount')
        ];
        $values = [
            $db->quote($paymentId),
            $db->quote($invoiceId),
            $db->quote($amount)
        ];
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_invoice_payment'))
            ->columns(implode(', ', $columns))
            ->values(implode(', ', $values));
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            Log::add("Failed to insert invoice payment mapping: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }
    }

    /**
     * Wrapper method that records a payment by performing all the necessary steps.
     * This method demonstrates how you can tie the individual steps together within a transaction.
     *
     * @param   int     $invoiceId       The invoice ID.
     * @param   float   $amount          The payment amount.
     * @param   string  $txnId           The transaction ID.
     * @param   string  $paymentMethod   The payment method.
     * @param   float   $fee             The fee amount.
     * @param   int     $feePassedOn     Whether the fee is passed on.
     * @param   int     $status          The payment status.
     * @param   string  $paymentDate     (Optional) The payment date.
     *
     * @return  int|false  The new payment ID on success, or false on failure.
     */
    public static function recordPayment($invoiceId, $amount, $txnId, $paymentMethod, $fee = 0, $feePassedOn = 0, $status = 1, $paymentDate = null)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $now = date('Y-m-d H:i:s');
        $paymentDate = $paymentDate ?: $now;

        // Begin transaction
        $db->transactionStart();

        // Load invoice to get client and account IDs.
        $query = $db->getQuery(true)
            ->select($db->quoteName(['client_id', 'account_id']))
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . (int) $invoiceId);
        $db->setQuery($query);
        $invoiceRecord = $db->loadObject();

       

        if (!$invoiceRecord) {
            Log::add("Invoice ID $invoiceId not found", Log::ERROR, 'payment');
            $db->transactionRollback();
            return false;
        }
        $clientId = $invoiceRecord->client_id;
        $accountId = $invoiceRecord->account_id;

        // Update invoice status.
        if (!self::updateInvoiceStatus($invoiceId, 3)) {
            $db->transactionRollback();
            return false;
        }

        echo $query;
        print_r($invoiceRecord);
        echo $clientId;
        echo $accountId;
        die();

        // Insert payment record.
        $paymentId = self::insertPaymentRecord($clientId, $accountId, $amount, $paymentDate, $fee, $feePassedOn, $paymentMethod, $txnId, $status);
        if (!$paymentId) {
            $db->transactionRollback();
            return false;
        }

        // Allocate payment to invoice.
        if (!self::allocatePayment($paymentId, $invoiceId, $amount)) {
            $db->transactionRollback();
            return false;
        }

        $db->transactionCommit();
        Log::add("Payment recorded with ID $paymentId for Invoice $invoiceId", Log::INFO, 'payment');
        return $paymentId;
    }

    public static function getInvoice($invoiceId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . (int) $invoiceId);
        $db->setQuery($query);
        $invoice = $db->loadObject();

        if (!$invoice) {
            throw new \RuntimeException("Invoice ID $invoiceId not found");
        }

        return $invoice;
    }


}
