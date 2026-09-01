<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class TicketsModel extends ListModel
{
    public function getItems()
    {
        $db      = $this->getDatabase();
        $isAdmin = Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_mothership');

        $query = $db->getQuery(true)
            ->select(
                't.*, a.name AS account_name, c.name AS client_name, '
                . '(SELECT COUNT(*) FROM ' . $db->quoteName('#__mothership_comments', 'r')
                . ' WHERE r.context = ' . $db->quote('ticket') . ' AND r.resource_id = t.id) AS reply_count'
            )
            ->from($db->quoteName('#__mothership_tickets', 't'))
            ->join('LEFT', $db->quoteName('#__mothership_accounts', 'a') . ' ON a.id = t.account_id')
            ->join('LEFT', $db->quoteName('#__mothership_clients', 'c') . ' ON c.id = t.client_id');

        if (!$isAdmin) {
            $clientIds = MothershipHelper::getUserClientIds();
            if (!$clientIds) {
                return [];
            }
            $ids = implode(',', array_map('intval', (array) $clientIds));
            $query->where("t.client_id IN ($ids)");
        }

        // Open tickets first, resolved/closed sink to the bottom; newest within each.
        $query->order("CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END ASC, t.created DESC");

        $db->setQuery($query);

        return $db->loadObjectList();
    }
}
