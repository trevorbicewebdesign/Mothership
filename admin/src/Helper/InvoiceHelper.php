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
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; 

class InvoiceHelper
{
    /**
     * Returns the invoice status as a string based on the provided status ID.
     *
     * @param int $status_id The status ID of the invoice.
     *                      1 = Draft
     *                      2 = Opened
     *                      3 = Cancelled
     *                      4 = Closed
     * @return string The corresponding status as a string. Returns 'Unknown' if the status ID does not match any known status.
     */
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

    /**
     * Determines if the invoice with the given ID is late based on its due date.
     *
     * Retrieves the due date for the specified invoice from the database and compares it
     * to the current date and time in the 'America/Los_Angeles' timezone. If the due date
     * is not found or cannot be parsed, the method returns false. If the current date and
     * time is after the due date, the invoice is considered late.
     *
     * @param int $invoiceId The ID of the invoice to check.
     *
     * @return bool True if the invoice is late, false otherwise.
     */
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

    /**
     * Retrieves the due date string for a given invoice ID.
     *
     * This method queries the database for the due date of the specified invoice,
     * then formats and returns it as a string using the getDueStringFromDate method.
     *
     * @param int $invoice_id The ID of the invoice to retrieve the due date for.
     * @return string The formatted due date string.
     */
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

    /**
     * Generates a human-readable string indicating the time difference between the current date
     * and a given due date. The output varies depending on the time difference:
     * - If no due date is provided, it returns "No due date".
     * - If the difference is 23.5 hours or more, it shows the difference in days.
     * - If the difference is 30 minutes or more but less than 23.5 hours, it shows the difference in hours.
     * - If the difference is less than 30 minutes, it returns "Due soon" for future dates or "Just now" for past dates.
     * 
     * @param string|null $dueDate The due date in a string format. Can be null.
     * @return string A human-readable string describing the time difference.
     */
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

    /**
     * Updates the status of an invoice in the database.
     *
     * @param int $invoiceId The ID of the invoice to update.
     * @param int $status The new status to set for the invoice.
     * 
     * @return bool Returns true if the update was successful, false otherwise.
     * 
     * @throws \Exception If there is an error during the database operation.
     * 
     * Logs an error message if the update fails.
     */
    public static function updateInvoiceStatus($invoice, $status): bool
    {
        $paidDate = null;

        try {
            $invoice = self::getInvoice($invoice->id);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        switch ($status) {
            case 1: // Draft
            case 2: // Opened
            case 3: // Cancelled
                break;
            case 4: // Closed
                $paidDate = date('Y-m-d H:i:s');
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_invoices'))
            ->set($db->quoteName('status') . ' = ' . (int) $status);

        if ($paidDate !== null) {
            $query->set($db->quoteName('paid_date') . ' = ' . $db->quote($paidDate));
        }

        $query->where($db->quoteName('id') . ' = ' . (int) $invoice->id);

        $db->transactionStart();

        try {
            $db->setQuery($query)->execute();

            // Update object & run hooks
            $invoice->status = $status;

            if ($status === 4) {
                self::onInvoiceClosed($invoice, $status);
            } elseif ($status === 2) {
                self::onInvoiceOpened($invoice, $status);
            }

            $db->transactionCommit();
        } catch (\Exception $e) {
            $db->transactionRollback();
            throw $e;
        }

        return true;
    }


    /**
     * Retrieves an invoice object from the database by its ID.
     *
     * @param  int  $invoice_id  The ID of the invoice to retrieve.
     * @return object            The invoice object.
     * @throws \RuntimeException If the invoice with the given ID is not found.
     */
    public static function getInvoice($invoice_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('*')
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
     * Recalculates and returns the status of an invoice based on the total payments made.
     *
     * This method retrieves the total amount paid for a given invoice and compares it to the invoice total.
     * It returns the status code:
     * - 2: Opened (default)
     * - 4: Closed (if fully paid)
     *
     * @param int $invoiceId The ID of the invoice to recalculate the status for.
     * @return int The new status code for the invoice.
     */
    public static function recalculateInvoiceStatus(int $invoiceId): int
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

        // Load invoice total and current status
        $query = $db->getQuery(true)
            ->select('total, status')
            ->from($db->quoteName('#__mothership_invoices'))
            ->where($db->quoteName('id') . ' = :invoiceId')
            ->bind(':invoiceId', $invoiceId, ParameterType::INTEGER);

        $db->setQuery($query);
        $row = $db->loadAssoc();

        // Defensive: handle missing or null values
        $invoiceTotal = isset($row['total']) ? (float)$row['total'] : 0.0;
        $currentStatus = isset($row['status']) ? (int)$row['status'] : 2;

        // Invoice total should only be recalculated if the invoice is opened or closed
        // Cancelled invoices should not be recalculated
        if( $currentStatus === 2 || $currentStatus === 4){
            if( $invoiceTotal > 0 && $totalPaid >= $invoiceTotal){
                return 4; // closed
            }
            else {
                return 2; // opened
            }
        }
        return $currentStatus; // no change
    }

    /**
     * Retrieves the list of payments associated with a specific invoice.
     *
     * @param int $invoiceId The ID of the invoice for which to retrieve payments.
     * @return array|null An array of payment objects associated with the invoice, or null if none found.
     * @throws \RuntimeException If there is an error retrieving the payments from the database.
     */
    public function getInvoicePayments($invoiceId)
    {
        $db = Factory::getContainer()->get(DatabaseDriver::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_invoice_payment'))
            ->where($db->quoteName('invoice_id') . ' = ' . (int) $invoiceId);
        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to retrieve payments for invoice ID {$invoiceId}: " . $e->getMessage());
        }
    }

     /**
     * Triggered when an invoice transitions to "Opened".
     *
     * @param  \Joomla\CMS\Table\Table  $invoice         The invoice table object.
     * @param  int                      $previousStatus  The previous status ID.
     *
     * @return void
     */
    public static function onInvoiceOpened($invoice, int $previousStatus): void
    {
        try {
            $client = ClientHelper::getClient($invoice->client_id);
        } catch (\Exception $e) {
            // bubble up the exception
            throw new \RuntimeException("Failed to get client: " . $e->getMessage());
        }

        // Get the owner id and load that user
        // Then grab the first name of that user
        $user = Factory::getUser($client->owner_user_id);
        $name = explode(" ", $user->name);
        $firstName = $name[0];
        $lastName = $name[1] ?? '';

        // Send the invoice email to the client
        EmailService::sendTemplate('invoice.user-opened', 
        $user->email, 
        "Invoice #{$invoice->number} Opened", 
        [
            'fname' => $firstName,
            'lname' => $lastName,
            'invoice' => $invoice,
            'client' => $client,
        ]);

        // Optional: add history or record in a log table
        LogHelper::logInvoiceStatusOpened(
            $invoice->id, 
            $invoice->client_id, 
            $invoice->account_id
        );

        \Joomla\CMS\Factory::getApplication()->triggerEvent('onMothershipInvoiceOpened', [$invoice]);
    }

    /**
     * Triggered when an invoice transitions to "Closed".
     *
     * @param  \Joomla\CMS\Table\Table  $invoice         The invoice table object.
     * @param  int                      $previousStatus  The previous status ID.
     *
     * @return void
     */
    public static function onInvoiceClosed($invoice, int $previousStatus): void
    {
        try {
            $client = ClientHelper::getClient($invoice->client_id);
            $account = AccountHelper::getAccount($invoice->account_id);
        } catch (\Exception $e) {
            
        }

        // Get the owner id and load that user
        // Then grab the first name of that user
        $user = Factory::getUser($client->owner_user_id);
        $name = explode(" ", $user->name);
        $firstName = $name[0];
        $lastName = $name[1] ?? '';

        // Send the invoice template to the client
        EmailService::sendTemplate('invoice.user-closed', 
        $user->email, 
        "Invoice #{$invoice->number} Closed", 
        [
            'fname' => $firstName,
            'lname' => $lastName,
            'invoice' => $invoice,
            'client' => $client,
            'account' => $account,
        ]);

        // Optional: add history or record in a log table
        LogHelper::logInvoiceStatusClosed(
            $invoice->id, 
            $invoice->client_id, 
            $invoice->account_id
        );

        // Event triggers after the invoice is closed
        \Joomla\CMS\Factory::getApplication()->triggerEvent('onMothershipInvoiceClosed', [$invoice]);
    }
}
