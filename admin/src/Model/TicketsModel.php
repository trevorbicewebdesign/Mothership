<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class TicketsModel extends ListModel
{
    use HasClientAccountFilter;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 't.id',
                'subject', 't.subject',
                'status', 't.status',
                'priority', 't.priority',
                'type', 't.type',
                'billable', 't.billable',
                'client_id', 't.client_id',
                'client_name', 'c.name',
                'account_id', 't.account_id',
                'account_name', 'a.name',
                'created', 't.created',
                'modified', 't.modified',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 't.created', $direction = 'desc')
    {
        $app = Factory::getApplication();

        if (empty($this->context)) {
            $this->context = $this->option . '.' . $this->getName();
        }

        $status = $app->getUserStateFromRequest("{$this->context}.filter.status", 'filter_status', '', 'string');
        $this->setState('filter.status', $status);

        $priority = $app->getUserStateFromRequest("{$this->context}.filter.priority", 'filter_priority', '', 'string');
        $this->setState('filter.priority', $priority);

        parent::populateState($ordering, $direction);

        // Shared, cascading Client / Account filter (see HasClientAccountFilter).
        // Runs AFTER parent so its raw filter[] read does not clobber it.
        $this->reconcileClientAccountFilterState();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.priority');
        $id = $this->clientAccountStoreId($id);

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('t.id'),
                    $db->quoteName('t.subject'),
                    $db->quoteName('t.status'),
                    $db->quoteName('t.priority'),
                    $db->quoteName('t.type'),
                    $db->quoteName('t.billable'),
                    $db->quoteName('t.client_id'),
                    $db->quoteName('t.account_id'),
                    $db->quoteName('t.created'),
                    $db->quoteName('t.modified'),
                    $db->quoteName('c.name', 'client_name'),
                    $db->quoteName('a.name', 'account_name'),
                ]
            )
        );

        $query->from($db->quoteName('#__mothership_tickets', 't'))
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON ' . $db->quoteName('t.client_id') . ' = ' . $db->quoteName('c.id'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON ' . $db->quoteName('t.account_id') . ' = ' . $db->quoteName('a.id'));

        // Reply count from the shared comments table.
        $sub = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__mothership_comments', 'cm'))
            ->where($db->quoteName('cm.context') . ' = ' . $db->quote('ticket'))
            ->where($db->quoteName('cm.resource_id') . ' = ' . $db->quoteName('t.id'));
        $query->select('(' . (string) $sub . ') AS ' . $db->quoteName('reply_count'));

        // Search by subject, or by "id:N" / "N".
        if ($search = trim($this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $sid = (int) substr($search, 3);
                $query->where($db->quoteName('t.id') . ' = :sid')->bind(':sid', $sid, ParameterType::INTEGER);
            } elseif (ctype_digit($search)) {
                $sid = (int) $search;
                $query->where($db->quoteName('t.id') . ' = :sid')->bind(':sid', $sid, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(' ', '%', $search) . '%';
                $query->where($db->quoteName('t.subject') . ' LIKE :search')->bind(':search', $like);
            }
        }

        if ($status = $this->getState('filter.status')) {
            $query->where($db->quoteName('t.status') . ' = :status')->bind(':status', $status);
        }

        if ($priority = $this->getState('filter.priority')) {
            $query->where($db->quoteName('t.priority') . ' = :priority')->bind(':priority', $priority);
        }

        // Filter by client / account (shared, cascading).
        $this->applyClientAccountFilterQuery($query, 't');

        // Open tickets first, then newest.
        $ordering  = $this->getState('list.ordering', 't.created');
        $direction = $this->getState('list.direction', 'DESC');

        $query->order(
            'CASE WHEN ' . $db->quoteName('t.status') . " IN ('resolved','closed') THEN 1 ELSE 0 END ASC, "
            . $db->quoteName($db->escape($ordering)) . ' ' . $db->escape($direction)
        );

        return $query;
    }

    public function delete($ids = [])
    {
        if (empty($ids)) {
            return false;
        }

        $ids = array_map('intval', (array) $ids);
        $db  = $this->getDatabase();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__mothership_tickets'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

        try {
            $db->setQuery($query)->execute();
            return true;
        } catch (\Exception $e) {
            $this->setError('Failed to delete tickets: ' . $e->getMessage());
            return false;
        }
    }
}
