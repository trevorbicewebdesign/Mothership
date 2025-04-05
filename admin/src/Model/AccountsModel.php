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

class AccountsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'client_name', 'c.name',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
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
            $db->quoteName('a.id'),
            $db->quoteName('a.name'),
            $db->quoteName('a.rate'),
            $db->quoteName('a.client_id'),
            $db->quoteName('a.created'),
            $db->quoteName('a.checked_out_time'),
            $db->quoteName('a.checked_out'),
            $db->quoteName('c.name', 'client_name')
            ]
            )
        );

        $query->from($db->quoteName('#__mothership_accounts', 'a'))
              ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('a.client_id') . ' = ' . $db->quoteName('c.id')); // Joining the client table

        // Filter by search in account name (or by account id if prefixed with "cid:").
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'cid:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('a.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('a.name') . ' LIKE :search')
                      ->bind(':search', $search);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'a.name'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
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

        // Since "published" doesn't apply for Accounts,
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
            ->update($db->quoteName('#__mothership_accounts'))
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

        // First, unlink any payments from these accounts
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_payments'))
            ->set($db->quoteName('account_id') . ' = NULL')
            ->where($db->quoteName('account_id') . ' IN (' . implode(',', $ids) . ')');

        try {
            $db->setQuery($query)->execute();
        } catch (\Exception $e) {
            $this->setError('Failed to unlink payments: ' . $e->getMessage());
            return false;
        }

        // Then delete the accounts
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mothership_accounts'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

        try {
            $db->setQuery($query)->execute();
            return true;
        } catch (\Exception $e) {
            $this->setError('Failed to delete accounts: ' . $e->getMessage());
            return false;
        }
    }


}
