<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class InvoicesModel extends ListModel
{
    public function getItems()
    {
        $clientId = MothershipHelper::getUserClientId();

        if (!$clientId) {
            return 0.0;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('i.*, a.name AS account_name')
            ->from('#__mothership_invoices AS i')
            ->join('LEFT', '#__mothership_accounts AS a ON i.client_id = a.client_id')
            ->where('i.status != -1')
            ->where('i.client_id = ' . (int) $clientId);
        $db->setQuery($query);

        return $db->loadObjectList();
    }
}