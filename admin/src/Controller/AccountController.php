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
        $app   = Factory::getApplication();
        $input = $app->input;
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Account');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ACCOUNT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=account&layout=edit', false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_ACCOUNT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        $task = $input->getCmd('task');

        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $defaultRedirect = Route::_("index.php?option=com_mothership&view=account&layout=edit&id={$id}", false);
        } else {
            $model->cancelEdit((int) $data['id']);
            $defaultRedirect = Route::_('index.php?option=com_mothership&view=accounts', false);
        }

        $this->setRedirect($defaultRedirect);
        return true;
    }



    public function cancel($key = null)
    {
        $model = $this->getModel('Account');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

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