<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class ProjectsModel extends ListModel
{
    public function getItems()
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return [];
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('p.*, c.name AS client_name, a.name AS account_name')
            ->from('#__mothership_projects AS p')
            ->join('LEFT', '#__mothership_clients AS c ON d.client_id = c.id')
            ->join('LEFT', '#__mothership_accounts AS a ON d.account_id = a.id')
            ->where("p.client_id = '{$clientId}'")
            ->order('p.name ASC');
        $db->setQuery($query);
        $items = $db->loadObjectList();
        
        return $items;
    }
}