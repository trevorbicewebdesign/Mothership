<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Ticket admin model.
 */
class TicketModel extends AdminModel
{
    public $typeAlias = 'com_mothership.ticket';

    protected function canEdit($record)
    {
        return $this->getCurrentUser()->authorise('core.edit', 'com_mothership');
    }

    protected function canDelete($record)
    {
        return $this->getCurrentUser()->authorise('core.delete', 'com_mothership');
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_mothership.ticket',
            'ticket',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_mothership.edit.ticket.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_mothership.ticket', $data);

        return $data;
    }

    /**
     * Read the comment thread for a ticket (for the read-only panel on the edit screen).
     */
    public function getComments($ticketId)
    {
        $ticketId = (int) $ticketId;
        if ($ticketId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['c.id', 'c.user_id', 'c.body', 'c.is_internal', 'c.created']))
            ->select($db->quoteName('u.name', 'author_name'))
            ->from($db->quoteName('#__mothership_comments', 'c'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('c.user_id'))
            ->where($db->quoteName('c.context') . ' = ' . $db->quote('ticket'))
            ->where($db->quoteName('c.resource_id') . ' = :tid')
            ->order($db->quoteName('c.created') . ' ASC')
            ->bind(':tid', $ticketId, \Joomla\Database\ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function prepareTable($table)
    {
        // Empty numeric form fields arrive as '' which STRICT_TRANS_TABLES rejects
        // for INT/DECIMAL columns (error 1366). Coerce blanks on the nullable
        // numeric columns to NULL before the row is stored. (client_id is NOT NULL
        // and required, so it is left for check() to validate.)
        foreach (['account_id', 'project_id', 'assigned_to', 'invoice_id',
                  'estimated_minutes', 'logged_minutes', 'rate'] as $col) {
            if (isset($table->$col) && $table->$col === '') {
                $table->$col = null;
            }
        }

        $now = Factory::getDate()->toSql();

        if (empty($table->id)) {
            if (empty($table->created)) {
                $table->created = $now;
            }
            if (empty($table->created_by)) {
                $table->created_by = (int) $this->getCurrentUser()->id;
            }
            if (empty($table->status)) {
                $table->status = 'new';
            }
        } else {
            $table->modified = $now;
        }

        // Stamp closed_at when moving into a terminal status; clear it otherwise.
        if (in_array($table->status, ['resolved', 'closed'], true)) {
            if (empty($table->closed_at)) {
                $table->closed_at = $now;
            }
        } else {
            $table->closed_at = null;
        }
    }
}
