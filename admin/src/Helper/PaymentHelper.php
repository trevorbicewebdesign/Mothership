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
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; // Ensure this is the correct namespace for LogHelper
use TrevorBice\Component\Mothership\Administrator\Service\EmailService; // Ensure this is the correct namespace for EmailService

class PaymentHelper
{

    public static function getPayment($paymentId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('client_id'),
                $db->quoteName('account_id'),
                $db->quoteName('amount'),
                $db->quoteName('payment_date'),
                $db->quoteName('fee_amount'),
                $db->quoteName('fee_passed_on'),
                $db->quoteName('payment_method'),
                $db->quoteName('transaction_id'),
                $db->quoteName('status'),
                $db->quoteName('processed_date'),
                $db->quoteName('created_at'),
                $db->quoteName('updated_at')
            ])
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

    public static function onPaymentCompleted($payment)
    {
        \Joomla\CMS\Factory::getApplication()->triggerEvent('onMothershipPaymentCompleted', [$payment]);

        // SEnd the invoice template to the client
        EmailService::sendTemplate('payment.user-confirmed', 
        'test.smith@mailinator.com', 
        'Payment Completed', 
        [
            'fname' => 'Trevor',
            'invoice_number' => 'INV-2045',
            'account_name' => 'Trevor Bice Webdesign',
            'account_center_url' => 'https://example.com/account',
            'invoice_due_date' => 'April 30, 2025',
            'pay_invoice_link' => 'https://example.com/pay?invoice=2045',
            'company_name' => 'Trevor Bice Webdesign',
            'company_address' => '123 Main St, San Francisco, CA',
            'company_address_1' => '123 Main St',
            'company_address_2' => 'Suite 100',
            'company_city' => 'San Francisco',
            'company_state' => 'CA',
            'company_zip' => '94111',
            'company_phone' => '(555) 555-5555',
            'company_email' => 'info@trevorbice.com',
        ]);

        // Optional: add history or record in a log table
        LogHelper::logPaymentCompleted($payment);
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
        try{
            $payment = self::getPayment($paymentId);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get payment record: " . $e->getMessage());
        }
        $old_status = $payment->status;
        $new_status = $status;

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('status') . ' = ' . (int) $status)
            ->where($db->quoteName('id') . ' = ' . (int) $paymentId);
        $db->setQuery($query);

        try {
            $db->execute();
            
        } catch (\Exception $e) {
            Log::add("Failed to update payment ID $paymentId: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }

        if($old_status !== $new_status && $new_status ==2){
            self::onPaymentCompleted($payment);
        }
        return true;
    }

    public static function updatePayment($paymendId, $data)
    {
        $allowedData = ['amount', 'fee', 'date', 'processed_date'];
        $data = array_intersect_key($data, array_flip($allowedData));
        if (empty($data)) {
            throw new \RuntimeException("No valid data provided for update.");
        }

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('amount') . ' = ' . (float) $data['amount'])
            ->set($db->quoteName('fee_amount') . ' = ' . (float) $data['fee'])
            ->set($db->quoteName('payment_date') . ' = ' . $db->quote($data['date']))
            ->set($db->quoteName('processed_date') . ' = ' . $db->quote($data['processed_date']))
            ->where($db->quoteName('id') . ' = ' . (int) $paymendId);
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            Log::add("Failed to update payment ID $paymendId: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }
    }

    public static function updateInvoicePayment($paymentId, $invoiceId, $applied_amount)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_invoice_payment'))
            ->set($db->quoteName('applied_amount') . ' = ' . (float) $applied_amount)
            ->where($db->quoteName('payment_id') . ' = ' . (int) $paymentId)
            ->where($db->quoteName('invoice_id') . ' = ' . (int) $invoiceId);
        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            Log::add("Failed to update invoice payment ID $paymentId: " . $e->getMessage(), Log::ERROR, 'payment');
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
            if (!$payment || empty($payment->id)) {
                throw new \RuntimeException("Payment not found: $paymentId");
            }
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
            $db->quote((int) $invoiceId),
            $db->quote((int) $paymentId),
            $db->quote((float) $applied_amount),
        ];
        //print_r($values);
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__mothership_invoice_payment'))
            ->columns(implode(', ', $columns))
            ->values(implode(', ', $values));
        //echo $query;
        $db->setQuery($query);

        try {
            $db->execute();
            $invoice_payment_id = $db->insertid();  
            
        } catch (\Exception $e) {
            Log::add("Failed to insert invoice payment record: " . $e->getMessage(), Log::ERROR, 'payment');
            return false;
        }

        if($invoice_payment_id == 0){
            throw new \RuntimeException("Failed to insert invoice payment record");
        }

        return $invoice_payment_id;
    }

    public function getPaymentInvoices($paymentId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('invoice_id, applied_amount')
            ->from($db->quoteName('#__mothership_invoice_payment'))
            ->where($db->quoteName('payment_id') . ' = ' . (int) $paymentId);
        $db->setQuery($query);

        try {
            $invoices = $db->loadObjectList();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get payment invoices: " . $e->getMessage());
        }

        return $invoices;
    }

    public static function handlePaymentCompleted(int $payment_id, string $txnId, float $amount, float $fee): void
    {
        $payment = self::getPayment($payment_id);
        $invoice = InvoiceHelper::getInvoice($payment->invoice_id);

        // Update payment details
        self::updatePayment($payment_id, [
            'transaction_id' => $txnId,
            'amount' => $amount,
            'fee' => $fee,
            'date' => date('Y-m-d H:i:s'),
            'processed_date' => date('Y-m-d H:i:s'),
            'status' => 2,
        ]);

        // Log the event
        LogHelper::logPaymentCompleted( $invoice->id );

        // Send email
        EmailService::sendTemplate('payment.user-completed', 
        $invoice->email, 
        'Payment Completed', 
        [
            'payment' => $payment,
            'invoice' => $invoice,
        ]);
    }



}
