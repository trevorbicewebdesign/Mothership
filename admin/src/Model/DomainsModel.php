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

class DomainsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'd.id',
                'client_id', 'd.client_id',
                'name', 'd.name',
                'client_name', 'c.name',
                'registrar', 'd.registrar',
                'reseller', 'd.reseller',
                'status', 'd.status',
                'dns_provider', 'd.dns_provider',
                'created', 'd.created',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'd.id', $direction = 'asc')
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
            $db->quoteName('d.id'),
            $db->quoteName('d.name'),
            $db->quoteName('d.client_id'),
            $db->quoteName('d.registrar'),
            $db->quoteName('d.reseller'),
            $db->quoteName('d.status'),
            $db->quoteName('d.dns_provider'),
            $db->quoteName('d.created'),
            $db->quoteName('c.name', 'client_name')
            ]
            )
        );

        $query->from($db->quoteName('#__mothership_domains', 'd'))
              ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('d.client_id') . ' = ' . $db->quoteName('c.id')); // Joining the client table

        // Filter by search in domain name (or by domain id if prefixed with "cid:").
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'cid:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('d.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('d.name') . ' LIKE :search')
                      ->bind(':search', $search);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'd.name'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
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

        // Since "published" doesn't apply for Domains,
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
            ->update($db->quoteName('#__mothership_domains'))
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

        // Then delete the domains
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mothership_domains'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

        try {
            $db->setQuery($query)->execute();
            return true;
        } catch (\Exception $e) {
            $this->setError('Failed to delete domains: ' . $e->getMessage());
            return false;
        }
    }


}
