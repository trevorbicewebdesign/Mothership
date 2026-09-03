<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_tickets
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Module\MothershipTickets\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_mothership_tickets.
 *
 * Tickets belonging to archived clients are excluded, matching the cascade
 * applied across the com_mothership admin lists.
 */
class MothershipTicketsHelper
{
    /**
     * The most recently updated tickets (open ones first, then by last activity).
     *
     * @param   int  $limit  Maximum rows to return.
     *
     * @return  array
     */
    public function getLatestTickets(int $limit = 5): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('t.id'),
                    $db->quoteName('t.subject'),
                    $db->quoteName('t.status'),
                    $db->quoteName('t.priority'),
                    $db->quoteName('t.created'),
                    $db->quoteName('t.modified'),
                    $db->quoteName('c.name', 'client_name'),
                ]
            )
            ->from($db->quoteName('#__mothership_tickets', 't'))
            ->join(
                'LEFT',
                $db->quoteName('#__mothership_clients', 'c')
                . ' ON ' . $db->quoteName('t.client_id') . ' = ' . $db->quoteName('c.id')
            )
            ->where('(' . $db->quoteName('c.archived') . ' = 0 OR ' . $db->quoteName('c.archived') . ' IS NULL)')
            // Open (not resolved/closed) first, then most recently touched.
            ->order(
                'CASE WHEN ' . $db->quoteName('t.status') . ' IN ('
                . $db->quote('resolved') . ', ' . $db->quote('closed') . ') THEN 1 ELSE 0 END ASC'
            )
            ->order('COALESCE(' . $db->quoteName('t.modified') . ', ' . $db->quoteName('t.created') . ') DESC');

        $db->setQuery($query, 0, max(1, $limit));

        try {
            return $db->loadObjectList() ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Count of open (not resolved/closed) tickets, archived clients excluded.
     *
     * @return  int
     */
    public function getOpenCount(): int
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__mothership_tickets', 't'))
            ->join(
                'LEFT',
                $db->quoteName('#__mothership_clients', 'c')
                . ' ON ' . $db->quoteName('t.client_id') . ' = ' . $db->quoteName('c.id')
            )
            ->where('(' . $db->quoteName('c.archived') . ' = 0 OR ' . $db->quoteName('c.archived') . ' IS NULL)')
            ->where($db->quoteName('t.status') . ' NOT IN (' . $db->quote('resolved') . ', ' . $db->quote('closed') . ')');

        $db->setQuery($query);

        try {
            return (int) $db->loadResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
