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

        // Load associated invoices 
        $query = $db->getQuery(true)
            ->select(['i.*'])
            ->from($db->quoteName('#__mothership_invoices', 'i'))
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

        // Load associated payments 
        $query = $db->getQuery(true)
            ->select(['d.*'])
            ->from($db->quoteName('#__mothership_domains', 'd'))
            ->where('account_id = :accountId')
            ->bind(':accountId', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $account->domains = $db->loadObjectList();

        return $account;
    }




    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('account.id', $id);
    }

}
