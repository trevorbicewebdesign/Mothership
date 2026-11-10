<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The email log as a list: everything the component tried to send, newest first.
 */
class EmailsModel extends ListModel
{
    use HasClientAccountFilter;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'e.id',
                'status', 'e.status',
                'subject', 'e.subject',
                'recipient', 'e.recipient',
                'template', 'e.template',
                'object_type', 'e.object_type',
                'client_id', 'e.client_id',
                'account_id', 'e.account_id',
                'user_id', 'e.user_id',
                'created', 'e.created',
                'sent_at', 'e.sent_at',
                'client_name', 'c.name',
                'account_name', 'a.name',
                'user_name', 'u.name',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'e.created', $direction = 'desc')
    {
        $this->setState('params', ComponentHelper::getParams('com_mothership'));

        parent::populateState($ordering, $direction);

        // Shared, cascading Client / Account selection (see HasClientAccountFilter). Must run after
        // parent::populateState(), and reads the request directly rather than via getState().
        $this->reconcileClientAccountFilterState();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.template');
        $id .= ':' . $this->getState('filter.object_type');
        $id  = $this->clientAccountStoreId($id);

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = EmailLog::describedQuery($db);

        // Search in the recipient, the subject or the cc; "id:12" finds one entry.
        if ($search = trim((string) $this->getState('filter.search', ''))) {
            if (stripos($search, 'id:') === 0) {
                $id = (int) substr($search, 3);
                $query->where($db->quoteName('e.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $like = '%' . str_replace(' ', '%', $search) . '%';
                $query->where('(' . $db->quoteName('e.recipient') . ' LIKE :s1 OR '
                    . $db->quoteName('e.subject') . ' LIKE :s2 OR '
                    . $db->quoteName('e.cc') . ' LIKE :s3)')
                    ->bind([':s1', ':s2', ':s3'], $like);
            }
        }

        if (($status = trim((string) $this->getState('filter.status', ''))) !== '') {
            $query->where($db->quoteName('e.status') . ' = :status')
                ->bind(':status', $status);
        }

        if (($template = trim((string) $this->getState('filter.template', ''))) !== '') {
            $query->where($db->quoteName('e.template') . ' = :template')
                ->bind(':template', $template);
        }

        if (($type = trim((string) $this->getState('filter.object_type', ''))) !== '') {
            $query->where($db->quoteName('e.object_type') . ' = :type')
                ->bind(':type', $type);
        }

        // Client / account. The trait's applyClientAccountFilterQuery() is not used: it hides
        // archived clients, and this is a record, so an archived client's email stays visible.
        if (($clientId = $this->getState('filter.client_id', '')) !== '' && $clientId !== null) {
            $cid = (int) $clientId;
            $query->where($db->quoteName('e.client_id') . ' = :fclient')
                ->bind(':fclient', $cid, ParameterType::INTEGER);
        }

        if (($accountId = $this->getState('filter.account_id', '')) !== '' && $accountId !== null) {
            $aid = (int) $accountId;
            $query->where($db->quoteName('e.account_id') . ' = :faccount')
                ->bind(':faccount', $aid, ParameterType::INTEGER);
        }

        $ordering  = $this->getState('list.ordering', 'e.created');
        $direction = strtoupper($this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $query->order($db->quoteName($db->escape($ordering)) . ' ' . $direction . ', ' . $db->quoteName('e.id') . ' ' . $direction);

        return $query;
    }

    /** Remove entries from the log. This recalls nothing; it only forgets. */
    public function delete($ids = []): bool
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        if (empty($ids)) {
            return false;
        }

        $db = $this->getDatabase();

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__mothership_emails'))
                    ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
            )->execute();

            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }
}
