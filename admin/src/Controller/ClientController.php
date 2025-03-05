<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

/**
 * Client Controller for com_mothership
 */
class ClientController extends FormController
{
    protected $default_view = 'client';

    /**
     * Centralized method to handle return URLs.
     */
    protected function getReturnRedirect($default = null)
    {
        $input = Factory::getApplication()->input;

        // First check the URL (common for cancel actions)
        $return = $input->getBase64('return');

        // If not in the URL, check form data
        if (!$return) {
            $data = $input->get('jform', [], 'array');
            if (!empty($data['return'])) {
                $return = base64_decode($data['return'], true);
                if ($return === false) {
                    return $default;
                }
                $return = urldecode($return);
            }
        }

        if ($return && filter_var($return, FILTER_VALIDATE_URL)) {
            return $return;
        }

        return $default;
    }


    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->get('jform', [], 'array');
        $model = $this->getModel('Client');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=client&layout=edit', false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_CLIENT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        $task = $input->getCmd('task');

        if ($task === 'apply') {
            $defaultRedirect = Route::_('index.php?option=com_mothership&view=client&layout=edit&id=' . $data['id'], false);
        } else {
            $defaultRedirect = Route::_('index.php?option=com_mothership&view=clients', false);
        }

        $this->setRedirect($this->getReturnRedirect($defaultRedirect));
        return true;
    }

    public function cancel($key = null)
    {
        $defaultRedirect = Route::_('index.php?option=com_mothership&view=accounts', false);
        $redirect = $this->getReturnRedirect($defaultRedirect);

        $this->setRedirect($redirect);

        return true;
    }

    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Client');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_CLIENT_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect($this->getReturnRedirect(Route::_('index.php?option=com_mothership&view=clients', false)));
    }
}
