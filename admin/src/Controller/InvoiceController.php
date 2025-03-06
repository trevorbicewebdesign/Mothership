<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;


/**
 * Invoice Controller for com_mothership
 */
class InvoiceController extends FormController
{
    protected $default_view = 'invoice';

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
        $model = $this->getModel('Invoice');

        if (!$model->save($data)) {
            // Error occurred, redirect back to form with error messages
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');

            // Determine which task was requested to redirect back to the appropriate edit page
            $task = $input->getCmd('task');
            if ($task === 'apply') {
            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoice&layout=edit&id=' . $data['id'], false);
            } else {
            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoice&layout=edit', false);
            }

            $this->setRedirect($redirectUrl);
            return false;
        }

        // Success message
        $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_INVOICE_SAVED_SUCCESSFULLY', "<strong>{$data['name']}</strong>"), 'message');

        // Determine which task was requested
        $task = $input->getCmd('task');

        // If "Apply" (i.e., invoice.apply) is clicked, remain on the edit page.
        if ($task === 'apply') {

            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoice&layout=edit&id=' . $data['id'], false);
        } else {
            // If "Save" (i.e., invoice.save) is clicked, return to the invoices list.
            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoices', false);
        }

        $this->setRedirect($redirectUrl);
        return true;
    }
}