<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Mpdf\Mpdf;
use Joomla\CMS\Layout\FileLayout;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;


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

    // Returns a list of accounts for a given client in JSON format
    public function getAccountsList()
    {
        $client_id = Factory::getApplication()->input->getInt('client_id');
        $accountList = AccountHelper::getAccountListOptions($client_id);
        echo json_encode($accountList);
        Factory::getApplication()->close();
    }

    public function getProjectsList()
    {
        $account_id = Factory::getApplication()->input->getInt('account_id');
        $projectList = ProjectHelper::getProjectListOptions($account_id);
        echo json_encode($projectList);
        Factory::getApplication()->close();
    }

    public function previewPdf()
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        $invoice = $model->getItem($id);
        $client = ClientHelper::getClient($invoice->client_id);
        $account = AccountHelper::getAccount($invoice->account_id);
        $business = MothershipHelper::getMothershipOptions();

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render([
            'invoice' => $invoice,
            'client' => $client,
            'account' => $account,
            'business' => $business
        ]);

        $app->close();
    }

    public function downloadPdf()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        $invoice = $model->getItem($id);

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        ob_start();
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render(['invoice' => $invoice]);
        $html = ob_get_clean();

        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output('Invoice-' . $invoice->number . '.pdf', 'I');

        $app->close();
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
                $redirectUrl = Route::_('index.php?option=com_mothership&view=invoices', false);
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
            $id = !empty($data['id']) ? $data['id'] : $model->getState($model->getName() . '.id');
            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoice&layout=edit&id=' . $id, false);
        } else {
            // If "Save" (i.e., invoice.save) is clicked, return to the invoices list.
            $redirectUrl = Route::_('index.php?option=com_mothership&view=invoices', false);
        }

        $this->setRedirect($redirectUrl);
        return true;
    }

    public function cancel($key = null)
    {
        $model = $this->getModel('Invoice');
        $id = $this->input->getInt('id');
        $model->cancelEdit($id);

        $defaultRedirect = Route::_('index.php?option=com_mothership&view=invoices', false);
        $returnRedirect = MothershipHelper::getReturnRedirect($defaultRedirect);

        $this->setRedirect($returnRedirect);

        return true;
    }

    public function unlock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        if ($model->unlock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_UNLOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_UNLOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=invoice&layout=edit&id={$id}", false));
    }

    public function lock($key = null)
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_INVOICE_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $model = $this->getModel('Invoice');
        if ($model->lock($id)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_LOCKED_SUCCESSFULLY'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVOICE_LOCK_FAILED'), 'error');
        }

        $this->setRedirect(Route::_("index.php?option=com_mothership&view=invoice&layout=edit&id={$id}", false));
    }
}