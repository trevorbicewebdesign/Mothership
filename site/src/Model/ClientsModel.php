<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class ClientsModel extends ListModel
{
    public function getItems()
    {
        $clientIds = MothershipHelper::getUserClientIds();

        if ($clientIds == null) {
            return [];
        }

        $db = $this->getDatabase();
        $id = $id ?? (int) $this->getState('client.id');

        $query = $db->getQuery(true)
            ->select('c.*, c.name AS client_name')
            ->from('#__mothership_clients AS c')
            ->where($db->quoteName('c.id') . ' IN (' . implode(',', array_map([$db, 'quote'], $clientIds)) . ')');
        $db->setQuery($query);
        $items = $db->loadObjectList();

        return $items;
    }
}