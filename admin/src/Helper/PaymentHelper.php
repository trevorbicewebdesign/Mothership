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
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; 
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;

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

    /**
     * Handles the actions to be performed when a payment is completed.
     *
     * This method performs the following tasks:
     * - Updates the invoice status to "closed".
     * - Sends a confirmation email to the user about the completed payment.
     * - Logs the payment completion in a history or log table.
     * - Triggers an event for other components to handle the payment completion.
     *
     * @param object $payment An object containing payment details, including the invoice ID.
     *
     * @return void
     */
    public static function onPaymentCompleted($payment)
    {
        try{
            $options = MothershipHelper::getMothershipOptions();
            $client = ClientHelper::getClient(client_id: $payment->client_id);
            $account = AccountHelper::getAccount($payment->account_id);
            
        }
        catch(\Exception $e){
            // error message should bubble up
            throw new \RuntimeException($e->getMessage());
        }

        // Get the owner id and load that user
        // Then grab the first name of that user
        $user = Factory::getUser($client->owner_user_id);
        $name = explode(" ", $user->name);
        $firstName = $name[0];
        $lastName = $name[1] ?? '';

        // Sends an email to the user that the payment has been completed
        EmailService::sendTemplate('payment.user-confirmed', 
            $client->email, 
            "Payment #{$payment->id} Received", 
            [
                'fname' => $firstName,
                'lname' => $lastName,
                'payment' => $payment,
                'client' => $client,
                'account' => $account,
            ]
        );

        // Send an email to the admin that the payment has been completed
        EmailService::sendTemplate('payment.admin-confirmed', 
            $options['company_email'], 
            "Payment #{$payment->id} Confirmed", 
            [
                'admin_fname' => 'Admin',
                'payment' => $payment,
                'client' => $client,
                'account' => $account,
            ]
        );

        // Log the payment completion
        LogHelper::logPaymentCompleted($payment);

        // Get all invoices associated with the payment
        // For now it should just be one invoice
        $invoices = PaymentHelper::getPaymentInvoices($payment->id);
        if (count($invoices) == 0) {
            throw new \RuntimeException("No invoices found for payment ID: {$payment->id}");
        }
        foreach($invoices as $invoice){
            // Recalculate the invoice status don't assume that the invoice is fully paid
            $new_invoice_status = InvoiceHelper::recalculateInvoiceStatus($invoice->id);
            InvoiceHelper::updateInvoiceStatus($invoice, $new_invoice_status);
        }

        // Trigger an event for other components to listen to
        \Joomla\CMS\Factory::getApplication()->triggerEvent('onMothershipPaymentCompleted', [$payment]);
    }

    /**
     * Returns the payment status as a string based on the provided status ID.
     *
     * @param int $status_id The status ID to convert (1: Pending, 2: Completed, 3: Failed, 4: Cancelled, 5: Refunded).
     * @return string The corresponding status as a string. Returns 'Unknown' if the status ID is not recognized.
     */
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

    /**
     * Returns an array of invoice objects associated with a payment.
     *
     * @param int $paymentId The payment ID.
     * @return array Array of invoice objects.
     */
    public static function getPaymentInvoices($paymentId): array
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('i.*')
            ->from($db->quoteName('#__mothership_invoice_payment', 'ip'))
            ->innerJoin(
                $db->quoteName('#__mothership_invoices', 'i') .
                ' ON ' . $db->quoteName('ip.invoice_id') . ' = ' . $db->quoteName('i.id')
            )
            ->where($db->quoteName('ip.payment_id') . ' = ' . (int) $paymentId);

        $db->setQuery($query);

        try {
            $invoices = $db->loadObjectList();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get payment invoices: " . $e->getMessage());
        }

        return $invoices;
    }
}
