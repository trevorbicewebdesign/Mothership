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
            return 0.0;
        }

        $db = $this->getDatabase();
        // Also need to get #__mothership_invoice_account table to list the invoices this account covers.
        
        $id = $id ?? (int) $this->getState('account.id');

        $query = $db->getQuery(true)
            ->select('a.*, a.name AS account_name ' )
                
            ->from('#__mothership_accounts AS a')
            ->where("a.id = '{$id}'");
        $db->setQuery($query);

        $items = $db->loadObjectList();

        return $items;
    }
}