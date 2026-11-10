<?php
namespace TrevorBice\Component\Mothership\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class ProposalModel extends BaseDatabaseModel
{
    public function getItem($id = null)
    {
        $id ??= (int) $this->getState('proposal.id');
        if (!$id) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(
            'proposal.* ' 
            )
            ->from('#__mothership_proposals AS proposal')
            ->where('proposal.id = ' . (int) $id)
        ;
        $db->setQuery($query);
        $proposal = $db->loadObject();

        // Only the client's own users and component administrators may load it.
        if ($proposal && !MothershipHelper::canAccessClient($proposal->client_id)) {
            return null;
        }

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
        $id = $app->getInput()->getInt('id');
        $this->setState('proposal.id', $id);
    }

}
