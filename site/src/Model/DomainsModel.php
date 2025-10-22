<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class DomainsModel extends ListModel
{
    public function getItems()
    {
        $clientIds = MothershipHelper::getUserClientIds();

        if (!$clientIds) {
            return [];
        }

        $db = $this->getDatabase();

        // Build a comma-separated list of integer client IDs for the query
        $clientIdsList = implode(',', array_map('intval', (array) $clientIds));

        $query = $db->getQuery(true)
            ->select('d.*,d.reseller, c.name AS client_name, a.name AS account_name')
            ->from('#__mothership_domains AS d')
            ->join('LEFT', '#__mothership_clients AS c ON d.client_id = c.id')
            ->join('LEFT', '#__mothership_accounts AS a ON d.account_id = a.id')
            ->where("d.client_id IN ($clientIdsList)")
            ->order('d.name ASC');
        $db->setQuery($query);
        $items = $db->loadObjectList();
        
        return $items;
    }
}