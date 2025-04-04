<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class DomainsModel extends ListModel
{
    public function getItems()
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return [];
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('d.*, c.name AS client_name ')
            ->from('#__mothership_domains AS d')
            ->join('LEFT', '#__mothership_clients AS c ON d.client_id = c.id')
            ->where("d.client_id = '{$clientId}'")
            ->order('d.name ASC');
        $db->setQuery($query);

        $items = $db->loadObjectList();

        return $items;
    }
}