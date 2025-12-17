<?php
namespace TrevorBice\Component\Mothership\Site\Model;

use Joomla\CMS\MVC\Model\ListModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;
use Joomla\CMS\Factory;

class ProposalsModel extends ListModel
{
    public function getItems()
    {
        $clientIds = MothershipHelper::getUserClientIds();

        if (!$clientIds) {
            $app = \Joomla\CMS\Factory::getApplication();
            $app->enqueueMessage("You do not have an associated client.", 'danger');
            return [];
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select([
            'proposal.*',
            'a.name AS account_name',
            'c.name AS client_name',

            // Lifecycle status
            'CASE ' . $db->quoteName('proposal.status') .
                ' WHEN 1 THEN ' . $db->quote('Draft') .
                ' WHEN 2 THEN ' . $db->quote('Pending') .
                ' WHEN 3 THEN ' . $db->quote('Approved') .
                'WHEN 4 THEN ' . $db->quote('Declined') .
                ' WHEN 5 THEN ' . $db->quote('Cancelled') .
                'WHEN 6 THEN ' . $db->quote('Expired') .
                ' ELSE ' . $db->quote('Unknown') . ' END AS status',
        ]);

        $query->from($db->quoteName('#__mothership_proposals', 'proposal'))
            ->join('LEFT', '#__mothership_accounts AS a ON proposal.account_id = a.id')
            ->join('LEFT', '#__mothership_clients AS c ON proposal.client_id = c.id')

            ->where($db->quoteName('proposal.status') . ' != 1')
            ->where($db->quoteName('proposal.client_id') . ' IN (' . implode(',', array_map('intval', $clientIds)) . ')');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

}