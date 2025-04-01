<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class PaymentsController extends BaseController
{
    /**
     * Display the list of Payments.
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
     * Check in selected payment items.
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
            $model = $this->getModel('Payments');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the list of IDs from the request.
        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Payments');
            if ($model->delete($ids)) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_DELETE_SUCCESS', count($ids), count($ids) > 1 ? 's' : ''), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PAYMENT_DELETE_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
    }

}
