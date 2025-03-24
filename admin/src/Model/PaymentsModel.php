<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class PaymentsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'p.id',
                'payment_date',
                'p.payment_date',
                'amount',
                'p.amount',
                'payment_method',
                'p.payment_method',
                'client_name',
                'c.name',
                'checked_out',
                'p.checked_out',
                'checked_out_time',
                'p.checked_out_time'
            ];
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'p.payment_date', $direction = 'asc')
    {
        $app = Factory::getApplication();
        if (empty($this->context)) {
            $this->context = $this->option . '.' . $this->getName();
        }
        $clientName = $app->getUserStateFromRequest("{$this->context}.filter.client_name", 'filter_client_name', '', 'string');
        $this->setState('filter.client_name', $clientName);

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        // Get a new query object.
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the payments table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('p.id'),
                    $db->quoteName('p.client_id'),
                    $db->quoteName('p.account_id'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('a.name', 'account_name'),
                    $db->quoteName('p.amount'),
                    $db->quoteName('p.payment_method'),
                    $db->quoteName('p.payment_date'),
                    'CASE ' . $db->quoteName('p.status') . 
                    ' WHEN 1 THEN ' . $db->quote('Pending') . 
                    ' WHEN 2 THEN ' . $db->quote('Completed') . 
                    ' WHEN 3 THEN ' . $db->quote('Failed') . 
                    ' WHEN 4 THEN ' . $db->quote('Cancelled') .
                    ' WHEN 5 THEN ' . $db->quote('Refunded') .
                    ' ELSE ' . $db->quote('Unknown') . ' END AS ' . $db->quoteName('status'),
                ]
            )
        );

        // Use unique aliases for each table.
        $query->from($db->quoteName('#__mothership_payments', 'p'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c')
                . ' ON ' . $db->quoteName('p.client_id') . ' = ' . $db->quoteName('c.id'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a')
                . ' ON ' . $db->quoteName('p.account_id') . ' = ' . $db->quoteName('a.id'));

        // Filter by search term.
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('p.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                // Since payments don't have a 'name', search on payment_method.
                $query->where($db->quoteName('p.payment_method') . ' LIKE :search')
                    ->bind(':search', $search);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'p.payment_date')))
            . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
        );

        return $query;
    }

    public function getItems()
    {
        $store = $this->getStoreId('getItems');
        if (!empty($this->cache[$store])) {
            return $this->cache[$store];
        }
        $items = parent::getItems();
        if (empty($items)) {
            return [];
        }
        $this->cache[$store] = $items;
        return $this->cache[$store];
    }

    public function checkin($ids = null)
    {
        if (empty($ids)) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_map('intval', $ids);
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('checked_out') . ' = 0')
            ->set($db->quoteName('checked_out_time') . ' = ' . $db->quote('0000-00-00 00:00:00'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $db->setQuery($query);
        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Deletes the specified payments and their related invoice payment links.
     *
     * @param array|int $ids An array of payment IDs or a single payment ID to delete.
     * @return bool True on success, false on failure.
     */
    public function delete($ids = [])
    {
        if (empty($ids)) {
            return false;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $db = $this->getDatabase();

        try {
            $db->transactionStart();

            foreach ($ids as $paymentId) {
                // Get related invoice IDs
                $query = $db->getQuery(true)
                    ->select($db->quoteName('invoice_id'))
                    ->from($db->quoteName('#__mothership_invoice_payments'))
                    ->where($db->quoteName('payment_id') . ' = :paymentId')
                    ->bind(':paymentId', $paymentId, ParameterType::INTEGER);
                $db->setQuery($query);
                $invoiceIds = $db->loadColumn();

                // Delete invoice_payment links
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__mothership_invoice_payments'))
                    ->where($db->quoteName('payment_id') . ' = :paymentId')
                    ->bind(':paymentId', $paymentId, ParameterType::INTEGER);
                $db->setQuery($query);
                $db->execute();

                // Recalculate invoice statuses
                foreach ($invoiceIds as $invoiceId) {
                    $this->recalculateInvoiceStatus((int) $invoiceId);
                }

                // Delete the payment itself
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__mothership_payments'))
                    ->where($db->quoteName('id') . ' = :paymentId')
                    ->bind(':paymentId', $paymentId, ParameterType::INTEGER);
                $db->setQuery($query);
                $db->execute();
            }

            $db->transactionCommit();
            return true;
        } catch (\Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
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
    protected function recalculateInvoiceStatus(int $invoiceId): void
    {
        $db = $this->getDatabase();

        // Calculate total payments for this invoice
        $query = $db->getQuery(true)
            ->select('SUM(p.amount)')
            ->from($db->quoteName('#__mothership_invoice_payments', 'ip'))
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


}
