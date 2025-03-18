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

class PaymentHelper
{

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

}
