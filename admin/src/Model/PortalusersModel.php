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
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Everyone who can use a client portal, one row per person with how many clients they reach in
 * each role. The person page (view=portaluser) lists the clients themselves.
 */
class PortalusersModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'client_id', 'role', 'status',
                'name', 'last_visit', 'client_count',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'name', $direction = 'asc')
    {
        $this->setState('params', ComponentHelper::getParams('com_mothership'));

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.client_id');
        $id .= ':' . $this->getState('filter.role');
        $id .= ':' . $this->getState('filter.status');

        return parent::getStoreId($id);
    }

    /**
     * Every grant as a row: owners from the client record, everyone else from the access table.
     * Someone given access to a client they also own counts once, as its owner.
     */
    private function grantsSql(): string
    {
        $db = $this->getDatabase();

        $owners = 'SELECT c.owner_user_id AS user_id, c.id AS client_id, c.name AS client_name,'
            . ' ' . $db->quote(ClientAccess::ROLE_OWNER) . ' AS role,'
            . ' ' . $db->quote('active') . ' AS status'
            . ' FROM ' . $db->quoteName('#__mothership_clients', 'c')
            . ' WHERE c.owner_user_id IS NOT NULL';

        $granted = 'SELECT mu.user_id, mu.client_id, c.name AS client_name, mu.role,'
            . ' CASE WHEN mu.accepted IS NOT NULL THEN ' . $db->quote('active')
            . ' WHEN mu.invite_expires < UTC_TIMESTAMP() THEN ' . $db->quote('expired')
            . ' ELSE ' . $db->quote('pending') . ' END AS status'
            . ' FROM ' . $db->quoteName('#__mothership_users', 'mu')
            . ' INNER JOIN ' . $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = mu.client_id'
            . ' WHERE c.owner_user_id IS NULL OR c.owner_user_id <> mu.user_id';

        return '(' . $owners . ' UNION ALL ' . $granted . ')';
    }

    protected function getListQuery()
    {
        $db     = $this->getDatabase();
        $grants = $this->grantsSql();

        // One row per person and role first, so each role can be counted once per person.
        $byRole = 'SELECT g.user_id, g.role, COUNT(*) AS clients, MIN(g.client_id) AS first_client_id,'
            . " SUM(g.status = 'pending') AS pending, SUM(g.status = 'expired') AS expired"
            . ' FROM ' . $grants . ' AS g GROUP BY g.user_id, g.role';

        $query = $db->getQuery(true)
            ->select([
                'u.id AS user_id', 'u.name', 'u.username', 'u.email', 'u.block', 'u.lastvisitDate AS last_visit',
                'SUM(pr.clients) AS client_count',
                'MIN(pr.first_client_id) AS first_client_id',
                'SUM(pr.pending) AS pending_count',
                'SUM(pr.expired) AS expired_count',
                "GROUP_CONCAT(CONCAT(pr.role, ':', pr.clients) ORDER BY FIELD(pr.role, 'owner', 'full_admin', 'account_admin', 'billing_admin') SEPARATOR ',') AS role_counts",
            ])
            ->from('(' . $byRole . ') AS pr')
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = pr.user_id')
            ->group('u.id, u.name, u.username, u.email, u.block, u.lastvisitDate');

        // Client and role narrow the people shown, but each person still counts all their clients.
        if (($clientId = (string) $this->getState('filter.client_id', '')) !== '') {
            $cid = (int) $clientId;
            $query->where('u.id IN (SELECT f.user_id FROM ' . $grants . ' AS f WHERE f.client_id = :client)')
                ->bind(':client', $cid, ParameterType::INTEGER);
        }

        if (($role = (string) $this->getState('filter.role', '')) !== '') {
            $query->where('u.id IN (SELECT r.user_id FROM ' . $grants . ' AS r WHERE r.role = :role)')
                ->bind(':role', $role);
        }

        if ($search = trim((string) $this->getState('filter.search', ''))) {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where('(u.name LIKE :s1 OR u.email LIKE :s2 OR u.id IN (SELECT s.user_id FROM ' . $grants . ' AS s WHERE s.client_name LIKE :s3))')
                ->bind(':s1', $like)
                ->bind(':s2', $like)
                ->bind(':s3', $like);
        }

        switch ((string) $this->getState('filter.status', '')) {
            case 'active':
                $query->having('SUM(pr.pending) + SUM(pr.expired) = 0');
                break;
            case 'pending':
                $query->having('SUM(pr.pending) > 0');
                break;
            case 'expired':
                $query->having('SUM(pr.expired) > 0');
                break;
        }

        $columns   = ['name' => 'u.name', 'last_visit' => 'u.lastvisitDate', 'client_count' => 'client_count'];
        $ordering  = $columns[$this->getState('list.ordering', 'name')] ?? 'u.name';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $query->order($ordering . ' ' . $direction . ', u.name ASC');

        return $query;
    }

    /** Unpacks each person's role counts into [role => number of clients]. */
    public function getItems()
    {
        $items = parent::getItems();

        // false means the query failed; the model holds the database error and the view shows it.
        if (!\is_array($items)) {
            return $items;
        }

        // People with a single client show its name instead of a count.
        $single = [];

        foreach ($items as $item) {
            if ((int) $item->client_count === 1) {
                $single[] = (int) $item->first_client_id;
            }
        }

        $db    = $this->getDatabase();
        $names = $single ? $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__mothership_clients'))
                ->whereIn($db->quoteName('id'), array_unique($single))
        )->loadAssocList('id', 'name') : [];

        foreach ($items as $item) {
            $item->single_client_name = (int) $item->client_count === 1 ? ($names[(int) $item->first_client_id] ?? null) : null;

            $counts = [];

            foreach (array_filter(explode(',', (string) $item->role_counts)) as $pair) {
                [$role, $n] = explode(':', $pair, 2) + [1 => 0];
                $counts[$role] = (int) $n;
            }

            $item->role_counts = $counts;
        }

        return $items;
    }
}
