<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class ExpensesController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }

    public function checkIn()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $ids   = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Expenses');
            if ($model->checkin($ids)) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $ids   = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Expenses');
            if ($model->delete($ids)) {
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_EXPENSE_DELETE_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_EXPENSE_DELETE_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=expenses', false));
    }
}
