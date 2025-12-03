<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class ProposalsController extends BaseController
{
    /**
     * Display the list of Proposals.
     *
     * @param   bool  $cachable   Should the view be cached
     * @param   array $urlparams  An array of safe url parameters and their variable types.
     *
     * @return  BaseController  A BaseController object to allow chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }

    /**
     * Check in selected account items.
     *
     * @return  void
     */
    public function checkIn()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the list of IDs from the request.
        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Proposals');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROPOSAL_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $db    = Factory::getDbo();

        $ids = array_map('intval', $input->get('cid', [], 'array'));

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $allowed = [];
        $blocked = [];

        // Filter proposal IDs: only allow draft proposals to be deleted
        foreach ($ids as $proposalId) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('status'))
                ->from($db->quoteName('#__mothership_proposals'))
                ->where($db->quoteName('id') . ' = ' . $proposalId);
            $db->setQuery($query);
            $status = (int) $db->loadResult();

            if ($status === 1) { // 0 = Draft
                $allowed[] = $proposalId;
            } else {
                $blocked[] = $proposalId;
            }
        }

        if (!empty($allowed)) {
            $model = $this->getModel('Proposals');

            if ($model->delete($allowed)) {
                $count = count($allowed);
                $app->enqueueMessage(
                    Text::sprintf('COM_MOTHERSHIP_PROPOSAL_DELETE_SUCCESS', $count, $count === 1 ? '' : 's'),
                    'message'
                );
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_DELETE_FAILED'), 'error');
            }
        }

        if (!empty($blocked)) {
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_MOTHERSHIP_PROPOSAL_DELETE_SKIPPED_NON_DRAFT',
                    implode(', ', $blocked)
                ),
                'warning'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
    }


}