<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class AccountModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('account.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load base account
        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__mothership_accounts', 'a'))
            ->where('a.id = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $account = $db->loadObject();

        if (!$account) {
            return null;
        }

         $query = $db->getQuery(true)
            ->select([
                
                'i.*, payment_ids',
                'COALESCE(pay.applied_amount, 0) AS applied_amount',
                'CASE' .
                    ' WHEN COALESCE(pay.applied_amount, 0) <= 0 THEN ' . $db->quote('Unpaid') .
                    ' WHEN COALESCE(pay.applied_amount, 0) < i.total THEN ' . $db->quote('Partially Paid') .
                    ' ELSE ' . $db->quote('Paid') .
                ' END AS payment_status'
            ])
            ->from('#__mothership_invoices AS i')
            ->leftJoin('#__mothership_invoice_payment AS pay ON pay.invoice_id = i.id')
            ->join(
                'LEFT',
                '(SELECT ip.invoice_id,
                         SUM(ip.applied_amount) AS total_paid,
                         GROUP_CONCAT(p.id ORDER BY p.payment_date) AS payment_ids
                  FROM ' . $db->quoteName('#__mothership_invoice_payment', 'ip') . '
                  JOIN ' . $db->quoteName('#__mothership_payments', 'p') . ' ON ip.payment_id = p.id
                  WHERE p.status = 2
                  GROUP BY ip.invoice_id) AS pay2
                ON pay2.invoice_id = i.id'
            )
            ->where('account_id = :accountId')
            ->where('i.status != 1')
            ->bind(':accountId', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $account->invoices = $db->loadObjectList();

        // Load associated payments 
        $query = $db->getQuery(true)
            ->select(['p.*'])
            ->from($db->quoteName('#__mothership_payments', 'p'))
            ->where('account_id = :accountId')
            ->where('p.status = 2')
            ->bind(':accountId', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $account->payments = $db->loadObjectList();

        // Load associated domains 
        $query = $db->getQuery(true)
            ->select(['d.*'])
            ->from($db->quoteName('#__mothership_domains', 'd'))
            ->where('account_id = :accountId')
            ->bind(':accountId', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $account->domains = $db->loadObjectList();

         // Load associated projects 
         $query = $db->getQuery(true)
            ->select(['p.*'])
            ->from($db->quoteName('#__mothership_projects', 'p'))
            ->where('account_id = :accountId')
            ->bind(':accountId', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $account->projects = $db->loadObjectList();

        return $account;
    }




    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('account.id', $id);
    }

}
