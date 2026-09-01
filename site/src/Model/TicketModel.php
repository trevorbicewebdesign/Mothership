<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

class TicketModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('ticket.id');
        if (!$id) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('t.*, a.name AS account_name, c.name AS client_name')
            ->from($db->quoteName('#__mothership_tickets', 't'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON a.id = t.account_id')
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = t.client_id')
            ->where('t.id = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /** Thread comments for a ticket, from the shared polymorphic comments table. */
    public function getComments($ticketId)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('r.*, u.name AS author_name')
            ->from($db->quoteName('#__mothership_comments', 'r'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = r.user_id')
            ->where('r.context = ' . $db->quote('ticket'))
            ->where('r.resource_id = :tid')
            ->bind(':tid', $ticketId, ParameterType::INTEGER)
            ->order('r.created ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /** Image attachments for a ticket, from the shared attachments table. */
    public function getAttachments($ticketId)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__mothership_attachments'))
            ->where('context = ' . $db->quote('ticket'))
            ->where('resource_id = :tid')
            ->bind(':tid', $ticketId, ParameterType::INTEGER)
            ->order('created ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    protected function populateState()
    {
        $this->setState('ticket.id', Factory::getApplication()->getInput()->getInt('id'));
    }
}
