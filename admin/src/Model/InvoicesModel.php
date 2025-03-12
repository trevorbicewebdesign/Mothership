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
                'checked_out', 'i.checked_out',
                'checked_out_time', 'i.checked_out_time',
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
        // Get a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
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
            //$db->quoteName('i.created'),
            $db->quoteName('i.checked_out_time'),
            $db->quoteName('i.checked_out'),
            ]
            )
        );

        $query->from($db->quoteName('#__mothership_invoices', 'i'))
              ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('i.client_id') . ' = ' . $db->quoteName('c.id'))
              ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON ' . $db->quoteName('i.account_id') . ' = ' . $db->quoteName('a.id'));

        // No filter by province as there is no 'state' column.

        // Filter by search in invoice name (or by invoice id if prefixed with "cid:").
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('i.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'i.id'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
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

    public function delete($ids = [])
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
            ->delete($db->quoteName('#__mothership_invoices'))
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

}
