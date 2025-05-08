<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;

\defined('_JEXEC') or die;


/**
 * Payment Controller for com_mothership
 */
class PaymentController extends FormController
{
    protected $default_view = 'payment';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    public function save($key = null, $urlVar = null)
    {
        $app   = Factory::getApplication();
        $input = $app->input;
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Payment');

        $payment = $model->getItem($data['id'] ?? 0);
        $new_payment_status = $data['status'] ?? null;

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payment&layout=edit', false));
            return false;
        }

        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        $task = $input->getCmd('task');

        if ($task === 'apply') {
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $defaultRedirect = Route::_("index.php?option=com_mothership&view=payment&layout=edit&id={$id}", false);
        } else {
            $defaultRedirect = Route::_('index.php?option=com_mothership&view=payments', false);
        }

        LogHelper::logStatusChange($payment, $new_payment_status);

        $this->setRedirect($defaultRedirect);
        return true;
    }

    public function confirm($key = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Payment');
        $id = $input->getInt('id');

        $payment = $model->getItem($id);
        
        if ($model->confirm($id)) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_CONFIRMED_SUCCESSFULLY', $id), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_CONFIRM_FAILED'), 'error');
        }

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=payments', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        LogHelper::logStatusChange($payment, 'Completed');
        PaymentHelper::onPaymentCompleted($payment);

        $this->setRedirect($redirect);

        return true;
    }



    public function cancel($key = null)
    {
        $model = $this->getModel('Payment');
        $id    = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=payments', false);
        $redirect = MothershipHelper::getReturnRedirect($defaultRedirect);
        $this->setRedirect($redirect);

        return true;
    }


    public function delete()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Payment');
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_NO_PAYMENT_SELECTED'), 'warning');
        } else {
            if (!$model->delete($cid)) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_DELETE_FAILED'), 'error');
                $app->enqueueMessage($model->getError(), 'error');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_DELETED_SUCCESSFULLY'), 'message');
            }
        }

        $this->setRedirect(MothershipHelper::getReturnRedirect(Route::_('index.php?option=com_mothership&view=payments', false)));
    }

    public function unlock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        $model = $this->getModel('Payment');
        if ($model->unlock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_UNLOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_UNLOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=payment&layout=edit&id={$id}", false));
    }

    public function lock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        $model = $this->getModel('Payment');
        if ($model->lock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_LOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_LOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=payment&layout=edit&id={$id}", false));
    }
}