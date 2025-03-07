<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Mpdf\Mpdf;
use Joomla\CMS\Layout\FileLayout;


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

    public function getAccountsForClient()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $input = $app->input;
        $clientId = $input->getInt('client_id');

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id, name')
            ->from('#__mothership_accounts')
            ->where('client_id = ' . (int) $clientId)
            ->order('name ASC');
        $db->setQuery($query);

        $accounts = $db->loadAssocList();

        echo new \Joomla\CMS\Response\JsonResponse($accounts);
        $app->close();
    }

    public function processPayment($invoice)
    {
        // Load payment plugins
        PluginHelper::importPlugin('payment');

        $dispatcher = \Joomla\CMS\Factory::getApplication();
        $results = $dispatcher->trigger('onMothershipPaymentRequest', [$invoice]);

        foreach ($results as $result) {
            if (!empty($result['status']) && $result['status'] === 'success') {
                return $result; // Payment succeeded
            }
        }

        return ['status' => 'failed', 'message' => 'Payment failed or no handler found.'];
    }

    public function pay()
    {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');

        $model = $this->getModel('Invoice');
        $invoice = $model->getItem($id);

        if (!$invoice) {
            $app->enqueueMessage('Invoice not found.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $result = $model->processPayment($invoice);

        if ($result['status'] === 'success') {
            $app->enqueueMessage('Payment successful!');
        } else {
            $app->enqueueMessage('Payment failed: ' . ($result['message'] ?? 'Unknown error'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
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

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render(['invoice' => $invoice]);

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