<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

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
                'state', 'a.state',
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

        // List state information.
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.purchase_type');

        return parent::getStoreId($id);
    }
    

    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $defaultPurchase = (int) ComponentHelper::getParams('com_mothership')->get('purchase_type', 3);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.name'),
                    $db->quoteName('a.contact_fname'),
                    $db->quoteName('a.contact_lname'),
                    $db->quoteName('a.contact_email'),
                    $db->quoteName('a.email'),
                    $db->quoteName('a.phone'),
                    $db->quoteName('a.address'),
                    $db->quoteName('a.address_1'),
                    $db->quoteName('a.address_2'),
                    $db->quoteName('a.city'),
                    $db->quoteName('a.state'),
                    $db->quoteName('a.zip'),
                    $db->quoteName('a.default_rate'),
                    $db->quoteName('a.tax_id'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_clients', 'a'));

        // Filter by published state
        $published = (string) $this->getState('filter.state');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.state') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.state') . ' IN (0, 1)');
        }

        $query->group(
            [
                $db->quoteName('a.id'),
                $db->quoteName('a.name'),
                $db->quoteName('a.checked_out'),
                $db->quoteName('a.checked_out_time'),
            ]
        );

        // Filter by search in title
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'cid:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('a.name') . ' LIKE :search')
                    ->bind(':search', $search);
            }
        }

        // Filter by purchase type
        if ($purchaseType = (int) $this->getState('filter.purchase_type')) {
            if ($defaultPurchase === $purchaseType) {
                $query->where('(' . $db->quoteName('a.purchase_type') . ' = :type OR ' . $db->quoteName('a.purchase_type') . ' = -1)');
            } else {
                $query->where($db->quoteName('a.purchase_type') . ' = :type');
            }

            $query->bind(':type', $purchaseType, ParameterType::INTEGER);
        }

        // Add the list ordering clause.
        $query->order(
            $db->quoteName($db->escape($this->getState('list.ordering', 'a.name'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC'))
        );

        return $query;
    }

    public function getItems()
    {
        
        // Get a storage key.
        $store = $this->getStoreId('getItems');

        // Try to load the data from internal storage.
        if (!empty($this->cache[$store])) {
            return $this->cache[$store];
        }

        // Load the list items.
        $items = parent::getItems();

        // If empty or an error, just return.
        if (empty($items)) {
            return [];
        }

        // Get the clients in the list.
        $db = $this->getDatabase();
        $clientIds = array_column($items, 'id');

        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('id'),
                    'COUNT(' . $db->quoteName('id') . ') AS ' . $db->quoteName('count_published'),
                ]
            )
            ->from($db->quoteName('#__mothership_clients'))
            ->where($db->quoteName('state') . ' = :state')
            ->whereIn($db->quoteName('id'), $clientIds)
            ->group($db->quoteName('id'))
            ->bind(':state', $state, ParameterType::INTEGER);

        $db->setQuery($query);

        // Get the published mothership count.
        try {
            $state          = 1;
            $countPublished = $db->loadAssocList('cid', 'count_published');
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // Get the unpublished mothership count.
        try {
            $state            = 0;
            $countUnpublished = $db->loadAssocList('cid', 'count_published');
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // Get the trashed mothership count.
        try {
            $state        = -2;
            $countTrashed = $db->loadAssocList('cid', 'count_published');
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // Get the archived mothership count.
        try {
            $state         = 2;
            $countArchived = $db->loadAssocList('cid', 'count_published');
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        // Inject the values back into the array.
        foreach ($items as $item) {
            $item->count_published   = $countPublished[$item->id] ?? 0;
            $item->count_unpublished = $countUnpublished[$item->id] ?? 0;
            $item->count_trashed     = $countTrashed[$item->id] ?? 0;
            $item->count_archived    = $countArchived[$item->id] ?? 0;
        }

        // Add the items to the internal cache.
        $this->cache[$store] = $items;

        return $this->cache[$store];
    }
}
