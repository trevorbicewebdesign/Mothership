<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DomainModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('domain.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the domain with status and related invoices
        $query = $db->getQuery(true)
            ->select([
                'd.*',
                'a.name AS account_name',
            ])
            ->from($db->quoteName('#__mothership_domains', 'd'))
            // need to left join the accounts table to get the account name
            ->leftJoin($db->quoteName('#__mothership_accounts', 'a'), 'a.id = d.account_id')
            ->where('d.id = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $domain = $db->loadObject();

        return $domain;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('domain.id', $id);
    }

}
