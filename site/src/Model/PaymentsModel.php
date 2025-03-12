<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class PaymentsModel extends ListModel
{
    public function getItems()
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return 0.0;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('p.*, a.name AS account_name, c.name AS client_name')
            ->from('#__mothership_payments AS p')
            ->join('LEFT', '#__mothership_accounts AS a ON p.account_id = a.id')
            ->join('LEFT', '#__mothership_clients AS c ON p.client_id = c.id')
            ->where("p.client_id = '{$clientId}'");
        $db->setQuery($query);

        return $db->loadObjectList();
    }
}