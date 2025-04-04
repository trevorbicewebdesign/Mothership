<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class AccountsModel extends ListModel
{
    public function getItems()
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return [];
        }

        $db = $this->getDatabase();
        $id = $id ?? (int) $this->getState('account.id');

        $query = $db->getQuery(true)
            ->select('a.*, a.name AS account_name')
            ->from('#__mothership_accounts AS a')
            ->where("a.client_id = '{$clientId}'"); // Ensure account belongs to the client
        $db->setQuery($query);
        $items = $db->loadObjectList();

        return $items;
    }
}