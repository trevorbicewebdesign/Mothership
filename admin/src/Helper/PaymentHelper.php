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
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;
use Joomla\Database\ParameterType;

class PaymentHelper
{

     /**
     * Recalculates the status of an invoice based on the total payments made.
     *
     * This method retrieves the total amount paid for a given invoice and compares it to the invoice total.
     * It then updates the invoice status to one of the following:
     * - 0: Unpaid
     * - 1: Partially Paid
     * - 2: Paid
     *
     * @param int $invoiceId The ID of the invoice to recalculate the status for.
     *
     * @return void
     */
    public static function recalculateInvoiceStatus(int $invoiceId): void
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);

        // Calculate total payments for this invoice
        $query = $db->getQuery(true)
            ->select('SUM(p.amount)')
            ->from($db->quoteName('#__mothership_invoice_payment', 'ip'))
            ->join('INNER', $db->quoteName('#__mothership_payments', 'p')
                . ' ON ' . $db->quoteName('ip.payment_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('ip.invoice_id') . ' = :invoiceId')
            ->bind(':invoiceId', $invoiceId, ParameterType::INTEGER);

        $db->setQuery($query);
        $totalPaid = (float) $db->loadResult();

        // Load invoice total
        $query = $db->getQuery(true)
            ->select('total')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = :invoiceId')
            ->bind(':invoiceId', $invoiceId, ParameterType::INTEGER);

        $db->setQuery($query);
        $invoiceTotal = (float) $db->loadResult();

        // Determine new status
        $status = 0; // e.g. 0 = Unpaid
        if ($totalPaid >= $invoiceTotal) {
            $status = 2; // Paid
        } elseif ($totalPaid > 0) {
            $status = 1; // Partially Paid
        }

        // Update invoice status
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_invoices'))
            ->set($db->quoteName('status') . ' = :status')
            ->where($db->quoteName('id') . ' = :invoiceId')
            ->bind(':status', $status, ParameterType::INTEGER)
            ->bind(':invoiceId', $invoiceId, ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();
    }

    public static function getPayment($paymentId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_payments'))
            ->where($db->quoteName('id') . ' = ' . (int) $paymentId);
        $db->setQuery($query);

        try {
            $payment = $db->loadObject();
            
        } catch (\Exception $e) {
          
            throw new \RuntimeException("Failed to get payment record: " . $e->getMessage());
        }
        return $payment;
    }

    public function getInvoicePayment($invoiceId, $paymentId)
    {

    }
    public static function updateStatus($paymentId, $status_id)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('status') . ' = ' . (int) $status_id)
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

    public static function getStatus($status_id)
    {
        // Transform the status from integer to string
        switch ($status_id) {
            case 1:
                $status = 'Pending';
                break;
            case 2:
                $status = 'Completed';
                break;
            case 3:
                $status = 'Failed';
                break;
            case 4:
                $status = 'Cancelled';
                break;
            case 5:
                $status = 'Refunded';
                break;
            default:
                $status = 'Unknown';
                break;
        }

        return $status;
    }

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

    public static function insertInvoicePayments($invoiceId, $paymentId, $applied_amount)
    {
        try{
            $invoice = InvoiceHelper::getInvoice($invoiceId);
        }
        catch(\Exception $e){
            // error message should bubble up
            throw new \RuntimeException($e->getMessage());
        }

        // must have valid payment ID
        try {
            $payment = self::getPayment($paymentId);
        }
        catch(\Exception $e){
            // error message should bubble up
            throw new \RuntimeException($e->getMessage());
        }

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $columns = [
            $db->quoteName('invoice_id'),
            $db->quoteName('payment_id'),
            $db->quoteName('applied_amount'),
        ];
        $values = [
            (string) (int) $invoiceId,
            (string) (int) $paymentId,
            (string) (float) $applied_amount,
        ];
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_invoice_payment'))
            ->columns(implode(', ', $columns))
            ->values(implode(', ', $values));
        $db->setQuery($query);

        try {
            $db->execute();
            return $db->insertid();
        } catch (\Exception $e) {
            Log::add("Failed to insert invoice payment record: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }
    }

}
