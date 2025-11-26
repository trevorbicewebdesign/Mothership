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

class EstimateHelper
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


    public static function setInvoiceClosed($invoiceId)
    {
        self::updateInvoiceStatus($invoiceId, 4);
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
     * Retrieves an estimate object from the database by its ID.
     *
     * @param  int  $estimate_id  The ID of the estimate to retrieve.
     * @return object             The estimate object.
     * @throws \RuntimeException  If the estimate with the given ID is not found.
     */
    public static function getEstimate($estimate_id)
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_estimates'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($estimate_id));

        $db->setQuery($query);
        $estimate = $db->loadObject();

        if (!$estimate) {
            throw new \RuntimeException("Estimate ID {$estimate_id} not found.");
        }

        return $estimate;
    }
}
