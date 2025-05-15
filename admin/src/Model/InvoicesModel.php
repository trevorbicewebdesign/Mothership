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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class InvoicesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'i.id',
                'client_name', 'c.name',
                'account_name', 'a.name',
                'number', 'i.number',
                'created', 'i.created',
                'account_id', 'i.account_id',
                'total', 'i.total',
                'client_id', 'i.client_id',
                'locked', 'i.locked',
                'checked_out', 'i.checked_out',
                'checked_out_time', 'i.checked_out_time',
                'created', 'i.created',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        $app = Factory::getApplication();

        // Ensure context is set
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
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('i.id'),
                    $db->quoteName('i.number'),
                    $db->quoteName('i.client_id'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('i.account_id'),
                    $db->quoteName('a.name', 'account_name'),
                    $db->quoteName('i.total'),
                    $db->quoteName('i.checked_out_time'),
                    $db->quoteName('i.checked_out'),
                    $db->quoteName('i.locked'),
                    $db->quoteName('pay.payment_ids'),
                    $db->quoteName('i.created'),
                    $db->quoteName('i.created_by'),      
                    $db->quoteName('i.project_id'),
                    $db->quoteName('p.name', 'project_name'),

                    // Invoice status (Draft, Opened, etc.)
                    'CASE ' . $db->quoteName('i.status') . 
                        ' WHEN 1 THEN ' . $db->quote('Draft') . 
                        ' WHEN 2 THEN ' . $db->quote('Opened') . 
                        ' WHEN 3 THEN ' . $db->quote('Cancelled') . 
                        ' WHEN 4 THEN ' . $db->quote('Closed') .
                        ' ELSE ' . $db->quote('Unknown') . ' END AS ' . $db->quoteName('status'),

                    // 👇 Add total_paid and payment_status
                    'COALESCE(pay.total_paid, 0) AS total_paid',
                    'CASE' .
                        ' WHEN COALESCE(pay.total_paid, 0) <= 0 THEN ' . $db->quote('Unpaid') .
                        ' WHEN COALESCE(pay.total_paid, 0) < i.total THEN ' . $db->quote('Partially Paid') .
                        ' ELSE ' . $db->quote('Paid') .
                    ' END AS payment_status'
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_invoices', 'i'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('i.client_id') . ' = ' . $db->quoteName('c.id'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON ' . $db->quoteName('i.account_id') . ' = ' . $db->quoteName('a.id'))
            ->join('LEFT', $db->quoteName('#__mothership_projects', 'p') . ' ON ' . $db->quoteName('i.project_id') . ' = ' . $db->quoteName('p.id'))

            // 👇 JOIN: Pull total completed payments per invoice
            ->join(
                'LEFT',
                '(SELECT ip.invoice_id,
                         SUM(ip.applied_amount) AS total_paid,
                         GROUP_CONCAT(p.id ORDER BY p.payment_date) AS payment_ids
                  FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip') . '
                  JOIN ' . $db->quoteName('#__mothership_payments', 'p') . ' ON ip.payment_id = p.id
                  WHERE p.status = 2
                  GROUP BY ip.invoice_id) AS pay
                ON pay.invoice_id = i.id'
            );

        // Filter by ID search
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('i.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            }
        }

        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'i.id'))) . ' ' .
            $db->escape($this->getState('list.direction', 'ASC'))
        );

        return $query;
    }


    public function getItems()
    {
        // Get a unique cache key.
        $store = $this->getStoreId('getItems');

        // Return from cache if available.
        if (!empty($this->cache[$store])) {
            return $this->cache[$store];
        }

        // Load the list items.
        $items = parent::getItems();

        // If no items or an error occurred, return an empty array.
        if (empty($items)) {
            return [];
        }

        // Since "published" doesn't apply for Invoices,
        // we simply return the items without additional counting logic.
        $this->cache[$store] = $items;

        return $this->cache[$store];
    }

    public function checkin($ids = null)
    {
        // Ensure we have valid IDs
        if (empty($ids)) {
            return false;
        }
        
        // Convert a single ID into an array
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        // Sanitize IDs to integers
        $ids = array_map('intval', $ids);
        
        $db = $this->getDatabase();
    
        // Build the query using an IN clause for multiple IDs
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_invoices'))
            ->set($db->quoteName('checked_out') . ' = 0')
            ->set($db->quoteName('checked_out_time') . ' = ' . $db->quote('0000-00-00 00:00:00'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        
        $db->setQuery($query);
    
        try {
            $db->execute();
            return true;
        }
        catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    public function canDeleteInvoice($record): bool
    {
        $id = (int) ($record->id ?? $record['id'] ?? 0);
        $status = (int) ($record->status ?? $record['status'] ?? null);

        if ($status !== 1) {
            return false; // Only allow drafts
        }

        return true;
    }

    public function delete($ids = [])
    {
        if (empty($ids)) {
            return [
                'deleted' => [],
                'skipped' => [],
            ];
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $db = $this->getDatabase();

        $deletableIds = [];
        $skippedIds   = [];

        foreach ($ids as $id) {
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'status']))
                ->from($db->quoteName('#__mothership_invoices'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $record = $db->setQuery($query)->loadObject();

            if ($record && $this->canDeleteInvoice($record)) {
                $deletableIds[] = $id;
            } else {
                $skippedIds[] = $id;
            }
        }

        if (empty($deletableIds)) {
            return [
                'deleted' => [],
                'skipped' => $skippedIds,
            ];
        }

        try {
            $db->transactionStart();

            // Delete linked invoice_payment rows
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoice_payment'))
                ->where($db->quoteName('invoice_id') . ' IN (' . implode(',', $deletableIds) . ')');
            $db->setQuery($query)->execute();

            // Delete invoices
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__mothership_invoices'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', $deletableIds) . ')');
            $db->setQuery($query)->execute();

            $db->transactionCommit();

            return [
                'deleted' => $deletableIds,
                'skipped' => $skippedIds,
            ];
        } catch (\Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());

            return [
                'deleted' => [],
                'skipped' => $ids,
            ];
        }
    }

}
