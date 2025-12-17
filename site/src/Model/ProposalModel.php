<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ProposalModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id = $id ?? (int) $this->getState('proposal.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();

        // Load the proposal
        $query = $db->getQuery(true)
            ->select(
            'i.*, ' .
            // Get a comma-separated list of payment IDs for this proposal
            '(SELECT GROUP_CONCAT(ip.payment_id) FROM #__mothership_proposal_payment AS ip WHERE ip.proposal_id = i.id) AS payment_ids, ' .
            'COALESCE(pay.applied_amount, 0) AS applied_amount, ' .
            'CASE ' .
                'WHEN COALESCE(pay.applied_amount, 0) <= 0 THEN ' . $db->quote('Unpaid') . ' ' .
                'WHEN COALESCE(pay.applied_amount, 0) < i.total THEN ' . $db->quote('Partially Paid') . ' ' .
                'ELSE ' . $db->quote('Paid') . ' ' .
            'END AS payment_status'
            )
            ->from('#__mothership_proposals AS i')
            ->leftJoin('#__mothership_proposal_payment AS pay ON pay.proposal_id = i.id')
            ->where('i.id = ' . (int) $id)
            ->where('i.status != 1');
        $db->setQuery($query);
        $proposal = $db->loadObject();

        if ($proposal) {
            // Load related items
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__mothership_proposal_items')
                ->where('proposal_id = ' . (int) $proposal->id);
            $db->setQuery($query);
            $proposal->items = $db->loadAssocList();
        }

        return $proposal;
    }

    protected function populateState()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $id = $app->input->getInt('id');
        $this->setState('proposal.id', $id);
    }

}
