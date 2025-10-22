<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class AccountsModel extends ListModel
{
    public function getItems()
    {
        $clientIds = MothershipHelper::getUserClientIds();

        if ($clientIds == null) {
            return [];
        }

        $db = $this->getDatabase();
        $id = $id ?? (int) $this->getState('account.id');

        $query = $db->getQuery(true)
            ->select('a.*, a.name AS account_name, c.name AS client_name')
            ->from('#__mothership_accounts AS a')
            ->join('INNER', '#__mothership_clients AS c ON c.id = a.client_id')
            ->where($db->quoteName('a.client_id') . ' IN (' . implode(',', array_map([$db, 'quote'], $clientIds)) . ')');
        $db->setQuery($query);
        $items = $db->loadObjectList();

        return $items;
    }
}