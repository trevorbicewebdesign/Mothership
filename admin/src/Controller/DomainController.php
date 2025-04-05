<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

\defined('_JEXEC') or die;


/**
 * Domain Controller for com_mothership
 */
class DomainController extends FormController
{
    protected $default_view = 'domain';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Domain');

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=domain&layout=edit', false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_DOMAIN_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        $task = $input->getCmd('task');

        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $defaultRedirect = Route::_("index.php?option=com_mothership&view=domain&layout=edit&id={$id}", false);
        } else {
            $defaultRedirect = Route::_('index.php?option=com_mothership&view=domains', false);
        }

        $this->setRedirect($defaultRedirect);
        return true;
    }



    public function cancel($key = null)
    {
        $model = $this->getModel('Domain');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=domains', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);
        $this->setRedirect($redirect);

        return true;
    }


    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Domain');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_DOMAIN_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_DOMAIN_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=domains', false)));
    }
}