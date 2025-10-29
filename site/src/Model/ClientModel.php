<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ClientModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('client.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load base account
        $query = $db->getQuery(true)
            ->select('c.*')
            ->from($db->quoteName('#__mothership_clients', 'c'))
            ->where('c.id = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $client = $db->loadObject();

        if (!$client) {
            return null;
        }

        return $client;
    }


    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('client.id', $id);
    }

}
