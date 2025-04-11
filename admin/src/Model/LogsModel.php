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
                'id', 'l.id',
                'client_id', 'l.client_id',
                'account_id', 'l.account_id',
                'object_type', 'l.object_type',
                'object_id', 'l.object_id',
                'action', 'l.action',
                'user_id', 'l.user_id',
                'created', 'l.created',
                'notes', 'l.notes',
                'client_name', 'c.name',
                'account_name', 'a.name',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'l.created', $direction = 'desc')
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
                    $db->quoteName('l.id'),
                    $db->quoteName('l.client_id'),
                    $db->quoteName('l.account_id'),
                    $db->quoteName('l.object_type'),
                    $db->quoteName('l.object_id'),
                    $db->quoteName('l.action'),
                    $db->quoteName('l.meta'),
                    $db->quoteName('l.user_id'),
                    $db->quoteName('l.created'),
                    $db->quoteName('l.notes'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('a.name', 'account_name'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_logs', 'l'))
                ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON l.client_id = c.id')
                ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON l.account_id = a.id');  



        // Filter by search in log name (or by log id if prefixed with "cid:").
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 4);
                $query->where($db->quoteName('l.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('l.notes') . ' LIKE :search')
                      ->bind(':search', $search);
            }
        }

        // Add the ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'l.created'))) . ' ' . $db->escape($this->getState('list.direction', 'DESC'))
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
