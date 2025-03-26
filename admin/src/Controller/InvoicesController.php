<?php
namespace TrevorBice\Component\Mothership\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

class InvoicesController extends BaseController
{
    /**
     * Display the list of Invoices.
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
     * Check in selected account items.
     *
     * @return  void
     */
    public function checkin()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        // Get the list of IDs from the request.
        $ids = $input->get('cid', [], 'array');

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        } else {
            $model = $this->getModel('Invoices');
            if ($model->checkin($ids)) {
                // this uses sprint f to insert the number of items checked in into the message
                $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_INVOICE_CHECK_IN_SUCCESS', count($ids)), 'message');
            } else {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_CHECK_IN_FAILED'), 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
    }

    public function delete()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        $ids = array_map('intval', $input->get('cid', [], 'array'));

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoices');

        // Let the model handle filtering and deletion
        $result = $model->delete($ids);

        if (isset($result['deleted']) && count($result['deleted']) > 0) {
            $count = count($result['deleted']);
            $app->enqueueMessage(
                Text::sprintf('COM_MOTHERSHIP_INVOICE_DELETE_SUCCESS', $count, $count > 1 ? 's' : ''),
                'message'
            );
        }

        if (isset($result['skipped']) && count($result['skipped']) > 0) {
            $app->enqueueMessage(
                Text::sprintf('COM_MOTHERSHIP_INVOICE_DELETE_SKIPPED_NON_DRAFT', count($result['skipped']), count($result['skipped']) > 1 ? 's' : ''),
                'warning'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
    }

}