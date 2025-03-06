<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

\defined('_JEXEC') or die;


/**
 * Account Controller for com_mothership
 */
class AccountController extends FormController
{
    protected $default_view = 'account';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        // Get the Joomla application and input
        $app = Factory::getApplication();
        $input = $app->input;

        // Get the submitted form data
        $data = $input->get('jform', [], 'array');

        // Get the model
        $model = $this->getModel('Account');

        if (!$model->save($data)) {
            // Error occurred, redirect back to form with error messages
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');

            // Determine which task was requested to redirect back to the appropriate edit page
            $task = $input->getCmd('task');
            if ($task === 'apply') {
            $redirectUrl = Route::_('index.php?option=com_mothership&view=account&layout=edit&id=' . $data['id'], false);
            } else {
            $redirectUrl = Route::_('index.php?option=com_mothership&view=account&layout=edit', false);
            }

            $this->setRedirect($redirectUrl);
            return false;
        }

        // Success message
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_ACCOUNT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        // Determine which task was requested
        $task = $input->getCmd('task');

        // If "Apply" (i.e., account.apply) is clicked, remain on the edit page.
        if ($task === 'apply') {

            $redirectUrl = Route::_('index.php?option=com_mothership&view=account&layout=edit&id=' . $data['id'], false);
        } else {
            // If "Save" (i.e., account.save) is clicked, return to the accounts list.
            $redirectUrl = Route::_('index.php?option=com_mothership&view=accounts', false);
        }

        $this->setRedirect($redirectUrl);
        return true;
    }

    public function cancel($key = null)
    {
        $defaultRedirect = Route::_('index.php?option=com_mothership&view=accounts', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Account');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_ACCOUNT_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=accounts', false)));
    }
}