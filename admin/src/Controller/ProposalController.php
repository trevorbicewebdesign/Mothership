<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Mpdf\Mpdf;
use Joomla\CMS\Layout\FileLayout;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;


\defined('_JEXEC') or die;


/**
 * Proposal Controller for com_mothership
 */
class ProposalController extends FormController
{
    protected $default_view = 'proposal';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    // Returns a list of accounts for a given client in JSON format
    public function getAccountsList()
    {
        $client_id = Factory::getApplication()->input->getInt('client_id');
        $accountList = AccountHelper::getAccountListOptions($client_id);
        echo json_encode($accountList);
        Factory::getApplication()->close();
    }

    public function getProjectsList()
    {
        $account_id = Factory::getApplication()->input->getInt('account_id');
        $projectList = ProjectHelper::getProjectListOptions($account_id);
        echo json_encode($projectList);
        Factory::getApplication()->close();
    }

    public function previewPdf()
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        $proposal = $model->getItem($id);
        $client = ClientHelper::getClient($proposal->client_id);
        $account = AccountHelper::getAccount($proposal->account_id);
        $business = MothershipHelper::getMothershipOptions();

        if (!$proposal) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PROPOSAL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $layout = new FileLayout('proposal-pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render([
            'proposal' => $proposal,
            'client' => $client,
            'account' => $account,
            'business' => $business
        ]);

        $app->close();
    }

    public function downloadPdf()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        $proposal = $model->getItem($id);
        $client = ClientHelper::getClient($proposal->client_id);
        $account = AccountHelper::getAccount($proposal->account_id);
        $business = MothershipHelper::getMothershipOptions();

        if (!$proposal) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PROPOSAL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        ob_start();
        $layout = new FileLayout('proposal-pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render([
            'proposal' => $proposal,
            'client' => $client,
            'account' => $account,
            'business' => $business
        ]);
        $html = ob_get_clean();

        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output('Proposal-' . $proposal->number . '.pdf', 'I');

        $app->close();
    }

    public function save($key = null, $urlVar = null)
    {
        // Get the Joomla application and input
        $app = Factory::getApplication();
        $input = $app->input;

        // Get the submitted form data
        $data = $input->get('jform', [], 'array');

        // Get the model
        $model = $this->getModel('Proposal');

        if (!$model->save($data)) {
            // Error occurred, redirect back to form with error messages
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');

            // Determine which task was requested to redirect back to the appropriate edit page
            $task = $input->getCmd('task');
            if ($task === 'apply') {
                $redirectUrl = Route::_('index.php?option=com_mothership&view=proposal&layout=edit&id=' . $data['id'], false);
            } else {
                $redirectUrl = Route::_('index.php?option=com_mothership&view=proposals', false);
            }

            $this->setRedirect($redirectUrl);
            return false;
        }

        // Success message
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PROPOSAL_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        // Determine which task was requested
        $task = $input->getCmd('task');

        // If "Apply" (i.e., proposal.apply) is clicked, remain on the edit page.
        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $redirectUrl = Route::_('index.php?option=com_mothership&view=proposal&layout=edit&id=' . $id, false);
        } else {
            // If "Save" (i.e., proposal.save) is clicked, return to the proposals list.
            $redirectUrl = Route::_('index.php?option=com_mothership&view=proposals', false);
        }

        $this->setRedirect($redirectUrl);
        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Proposal');
        $id = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=proposals', false);
        $returnRedirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        $this->setRedirect($returnRedirect);

        return true;
    }

    public function unlock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        if ($model->unlock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_UNLOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_UNLOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=proposal&layout=edit&id={$id}", false));
    }

    public function lock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        if ($model->lock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_LOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_LOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=proposal&layout=edit&id={$id}", false));
    }
}