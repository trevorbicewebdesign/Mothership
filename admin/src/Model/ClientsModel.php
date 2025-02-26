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
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class ClientsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'cid', 'a.id',
                'name', 'a.name',
                'contact', 'a.contact',
                'province', 'a.state', // Here, 'state' holds the province value.
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'purchase_type', 'a.purchase_type',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc')
    {
        // Load the parameters.
        $this->setState('params', ComponentHelper::getParams('com_mothership'));

        // Let the parent method set up list state (ordering, direction, etc.).
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.province');
        $id .= ':' . $this->getState('filter.purchase_type');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        // Get a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Get the default purchase type from the component parameters.
        $defaultPurchase = (int) ComponentHelper::getParams('com_mothership')->get('purchase_type', 3);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.name'),
                    $db->quoteName('a.email'),
                    $db->quoteName('a.phone'),
                    $db->quoteName('a.address_1'),
                    $db->quoteName('a.address_2'),
                    $db->quoteName('a.city'),
                    $db->quoteName('a.state'),
                    $db->quoteName('a.zip'),
                    $db->quoteName('a.default_rate'),
                    $db->quoteName('a.owner_user_id'),
                    $db->quoteName('a.tax_id'),
                    $db->quoteName('a.checked_out'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_clients', 'a'));

        // Filter by province (instead of published state).
        if ($province = trim($this->getState('filter.province', ''))) {
            $query->where($db->quoteName('a.state') . ' = :province')
                  ->bind(':province', $province);
        }

        // Filter by search in client name (or by client id if prefixed with "cid:").
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

        // Filter by purchase type.
        if ($purchaseType = (int) $this->getState('filter.purchase_type')) {
            if ($defaultPurchase === $purchaseType) {
                $query->where('(' . $db->quoteName('a.purchase_type') . ' = :type OR ' . $db->quoteName('a.purchase_type') . ' = -1)');
            } else {
                $query->where($db->quoteName('a.purchase_type') . ' = :type');
            }
            $query->bind(':type', $purchaseType, ParameterType::INTEGER);
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

        // Since "published" doesn't apply for clients,
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
            ->update($db->quoteName('#__mothership_clients'))
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
            ->delete($db->quoteName('#__mothership_clients'))
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
