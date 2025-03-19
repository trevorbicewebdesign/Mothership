<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;
use Joomla\CMS\Factory;

class InvoicesModel extends ListModel
{
    public function getItems()
    {
        $user = Factory::getUser();
        $userId = $user->id;
        $clientId = MothershipHelper::getUserClientId($userId);

        if (!$clientId) {
            // display a warning to the user
            $app = Factory::getApplication();
            $app->enqueueMessage("You do not have an associated client.", 'danger');
            return;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select(
                'i.*, a.name AS account_name, ' .
                'CASE ' . $db->quoteName('i.status') . 
                    ' WHEN 1 THEN ' . $db->quote('Draft') . 
                    ' WHEN 2 THEN ' . $db->quote('Opened') . 
                    ' WHEN 3 THEN ' . $db->quote('Late') . 
                    ' WHEN 4 THEN ' . $db->quote('Paid') .
                    ' ELSE ' . $db->quote('Unknown') . ' END AS ' . $db->quoteName('status')
                )
            ->from('#__mothership_invoices AS i')
            ->join('LEFT', '#__mothership_accounts AS a ON i.client_id = a.client_id')
            ->where("i.status != '1'")
            ->where("i.client_id = '{$clientId}'");
        $db->setQuery($query);
        

        return $db->loadObjectList();
    }
}