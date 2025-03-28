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
use Joomla\Database\ParameterType;

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
                $status = 'Cancelled';
                break;
            case 4:
                $status = 'Closed';
                break;
            default:
                $status = 'Unknown';
                break;
        }

        return $status;
    }

    public static function isLate(int $invoiceId): bool
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('due_date')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $invoiceId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $dueDate = $db->loadResult();

        if (!$dueDate) {
            return false;
        }

        $timezone = new \DateTimeZone('America/Los_Angeles');
        $now = new \DateTimeImmutable('now', $timezone);

        // Parse due date
        $due = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dueDate, $timezone);
        if (!$due) {
            // Fallback for Y-m-d → assume end of day (23:59:59)
            $due = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dueDate . ' 23:59:59', $timezone);
        }

        return $now > $due;
    }



    public static function getDueString(int $invoice_id): string
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('due_date')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = ' . (int) $invoice_id);
        $db->setQuery($query);

        $dueDate = $db->loadResult();
        return self::getDueStringFromDate($dueDate);
    }

    public static function getDueStringFromDate(?string $dueDate): string
    {
        if (!$dueDate) {
            return 'No due date';
        }

        $timezone = new \DateTimeZone('America/Los_Angeles');

        $now = new \DateTimeImmutable('now', $timezone);
        $due = (new \DateTimeImmutable($dueDate))->setTimezone($timezone);

        $diffInSeconds = $due->getTimestamp() - $now->getTimestamp();
        $isFuture = $diffInSeconds >= 0;
        $absDiff = abs($diffInSeconds);

        if ($absDiff >= (23.5 * 3600)) {
            // ≥ 23.5 hours → show in days
            $days = (int) round($absDiff / 86400);
            $label = "{$days} day" . ($days !== 1 ? 's' : '');
        } elseif ($absDiff >= (0.5 * 3600)) {
            // ≥ 30 minutes → show in hours
            $hours = (int) round($absDiff / 3600);
            $label = "{$hours} hour" . ($hours !== 1 ? 's' : '');
        } else {
            return $isFuture ? 'Due soon' : 'Just now';
        }

        return $isFuture ? "Due in {$label}" : "{$label} late";
    }


    public static function setInvoiceClosed($invoiceId)
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
        $status = 2; // e.g. 0 = Opened
        if ($totalPaid >= $invoiceTotal) {
            $status = 4; // Closed
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

    public function getInvoicePayments($invoiceId)
    {
        
    }
}
