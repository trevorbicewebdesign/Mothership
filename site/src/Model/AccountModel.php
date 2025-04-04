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

        // Load the account with status and related invoices
        $query = $db->getQuery(true)
            ->select([
                'a.*',
            ])
            ->from($db->quoteName('#__mothership_accounts', 'a'))
            ->where('a.id = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $account = $db->loadObject();

        return $account;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('account.id', $id);
    }

}
