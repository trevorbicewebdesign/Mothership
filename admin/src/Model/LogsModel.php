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

class LogsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'c.id',
                'name', 'c.name',
                'phone', 'c.phone',
                'created', 'c.created',
                'default_rate', 'c.rate',
                'checked_out', 'c.checked_out',
                'checked_out_time', 'c.checked_out_time',

            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'c.name', $direction = 'asc')
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
                    $db->quoteName('c.id'),
                    $db->quoteName('c.name'),
                    $db->quoteName('c.email'),
                    $db->quoteName('c.phone'),
                    $db->quoteName('c.address_1'),
                    $db->quoteName('c.address_2'),
                    $db->quoteName('c.city'),
                    $db->quoteName('c.state'),
                    $db->quoteName('c.zip'),
                    $db->quoteName('c.default_rate'),
                    $db->quoteName('c.owner_user_id'),
                    $db->quoteName('c.tax_id'),
                    $db->quoteName('c.created'),
                    $db->quoteName('c.checked_out'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_logs', 'c'));


        // Filter by search in log name (or by log id if prefixed with "cid:").
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('c.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('c.name') . ' LIKE :search')
                      ->bind(':search', $search);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'c.name'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
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

        $items = parent::getItems();

        if (empty($items)) {
            return [];
        }

        $this->cache[$store] = $items;

        return $this->cache[$store];
    }

    public function checkin($ids = null)
    {
        // Ensure we have valid IDs
        if (empty($ids)) {
            return false;
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $ids = array_map('intval', $ids);
        
        $db = $this->getDatabase();
    
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__mothership_logs'))
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
        if (empty($ids)) {
            return false;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mothership_logs'))
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
