<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

\defined('_JEXEC') or die;

/**
 * Log Controller for com_mothership
 */
class LogController extends FormController
{
    protected $default_view = 'log';

    /**
     * Logs are an audit trail written by the system. Nothing is added or edited by
     * hand, so the form routes refuse rather than opening an editor whose save
     * could never succeed.
     */
    protected function allowAdd($data = [])
    {
        return false;
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        return false;
    }

    public function edit($key = null, $urlVar = null)
    {
        return $this->refuseReadOnly();
    }

    public function save($key = null, $urlVar = null)
    {
        return $this->refuseReadOnly();
    }

    private function refuseReadOnly(): bool
    {
        $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_LOG_READ_ONLY'), 'warning');
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=logs', false));

        return false;
    }


    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Log');
        $id = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=logs', false);
        $returnRedirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        $this->setRedirect($returnRedirect);

        return true;
    }
    
    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Log');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_LOG_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_LOG_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_LOG_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=logs', false)));
    }

}
