<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;

\defined('_JEXEC') or die;

/**
 * Expense Controller for com_mothership
 */
class ExpenseController extends FormController
{
    protected $default_view = 'expense';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Expense');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=expense&layout=edit', false));
            return false;
        }

        $label = trim(($data['item'] ?? '') !== '' ? $data['item'] : ($data['merchant'] ?? ''));
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_SAVED_SUCCESSFULLY', '<strong>' . htmlspecialchars($label) . '</strong>'), 'message');

        $task = $input->getCmd('task');

        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $redirect = Route::_("index.php?option=com_mothership&view=expense&layout=edit&id={$id}", false);
        } else {
            $redirect = MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
        }

        $this->setRedirect($redirect);
        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Expense');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

        $redirect = MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $model = $this->getModel('Expense');
        $cid   = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_EXPENSE_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=expenses', false)));
    }

    // Returns a list of accounts for a given client in JSON format (cascade).
    public function getAccountsList()
    {
        $client_id   = Factory::getApplication()->getInput()->getInt('client_id');
        $accountList = AccountHelper::getAccountListOptions($client_id);
        echo json_encode($accountList);
        Factory::getApplication()->close();
    }

    // Returns a list of projects for a given account in JSON format (cascade).
    public function getProjectsList()
    {
        $account_id  = Factory::getApplication()->getInput()->getInt('account_id');
        $projectList = ProjectHelper::getProjectListOptions($account_id);
        echo json_encode($projectList);
        Factory::getApplication()->close();
    }
}
