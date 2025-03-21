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
        // Also need to get #__mothership_invoice_payment table to list the invoices this payment covers.
        
        $query = $db->getQuery(true)
            ->select('p.*, a.name AS account_name, c.name AS client_name, ' .
                'CASE ' . $db->quoteName('p.status') . 
                ' WHEN 1 THEN ' . $db->quote('Pending') . 
                ' WHEN 2 THEN ' . $db->quote('Completed') . 
                ' WHEN 3 THEN ' . $db->quote('Failed') . 
                ' WHEN 4 THEN ' . $db->quote('Cancelled') . 
                ' WHEN 5 THEN ' . $db->quote('Refunded') . 
                ' ELSE ' . $db->quote('Unknown') . ' END AS ' . $db->quoteName('status') . ', ' .
                'GROUP_CONCAT(ip.invoice_id) AS invoice_ids')
            ->from('#__mothership_payments AS p')
            ->join('LEFT', '#__mothership_accounts AS a ON p.account_id = a.id')
            ->join('LEFT', '#__mothership_clients AS c ON p.client_id = c.id')
            ->join('LEFT', '#__mothership_invoice_payment AS ip ON p.id = ip.payment_id')
            ->where("p.client_id = '{$clientId}'")
            ->group('p.id');
        $db->setQuery($query);

        $items = $db->loadObjectList();

        return $items;
    }
}