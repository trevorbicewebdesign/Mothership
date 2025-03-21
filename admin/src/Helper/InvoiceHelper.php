<?php
/**
 * Invoice Helper for Mothership Invoice Plugins
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

class InvoiceHelper
{

    public static function getStatus($status_id)
    {
        // Transform the status from integer to string
        switch ($status_id) {
            case 1:
                $status = 'Draft';
                break;
            case 2:
                $status = 'Opened';
                break;
            case 3:
                $status = 'Late';
                break;
            case 4:
                $status = 'Paid';
                break;
            default:
                $status = 'Unknown';
                break;
        }

        return $status;
    }

    public static function setInvoicePaid($invoiceId)
    {
        self::updateInvoiceStatus($invoiceId, 4);
    }

    public static function getInvoiceAppliedPayments($invoiceID)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_invoice_payment'))
            ->where($db->quoteName('invoice_id') . ' = ' . (int) $invoiceID);
        $db->setQuery($query);
        try {
            $invoicePayments = $db->loadObjectList();
        }
        catch (\Exception $e) {
            throw new \RuntimeException("Failed to get invoice payments: " . $e->getMessage());
        }

        return $invoicePayments;
    }

    public static function sumInvoiceAppliedPayments($invoiceId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('SUM(p.applied_amount)')
            ->from($db->quoteName('#__mothership_invoice_payment', 'p'))
            ->join('INNER', $db->quoteName('#__mothership_payments', 'mp') . ' ON ' . $db->quoteName('p.payment_id') . ' = ' . $db->quoteName('mp.id'))
            ->where($db->quoteName('p.invoice_id') . ' = ' . (int) $invoiceId)
            ->where($db->quoteName('mp.status') . ' = 2');
        $db->setQuery($query);
        try {
            $total = $db->loadResult();
        }
        catch (\Exception $e) {
            throw new \RuntimeException("Failed to sum invoice payments: " . $e->getMessage());
        }

        return (float) $total;
    }

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

    public static function getInvoice($invoice_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName([
                '*',
            ]))
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($invoice_id));

        $db->setQuery($query);
        $invoice = $db->loadObject();

        if (!$invoice) {
            throw new \RuntimeException("Invoice ID {$invoice_id} not found.");
        }

        return $invoice;
    }

}
