<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use Mpdf\Mpdf;

// Load all enabled payment plugins
PluginHelper::importPlugin('mothership-payment');

class InvoiceController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'invoice'));
        parent::display($cachable, $urlparams);
    }

    /**
     * Handles the download of an invoice as a PDF file.
     *
     * This method retrieves an invoice by its ID, generates a PDF representation
     * of the invoice, and streams it to the browser for download or inline viewing.
     *
     * @return void
     *
     * @throws Exception If there is an issue with output buffering or PDF generation.
     */
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
        $client = ClientHelper::getClient($invoice->client_id);
        $account = AccountHelper::getAccount($invoice->account_id);
        $business = MothershipHelper::getMothershipOptions();

        if (!$invoice) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVOICE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoices', false));
            return;
        }

        // Generate the HTML
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render([
            'invoice' => $invoice,
            'client' => $client,
            'account' => $account,
            'business' => $business
        ]);

        // Turn off Joomla's output
        ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Invoice-' . $invoice->number . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Generate and output the PDF
        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output(null, 'I');

        $app->close();
    }

    /**
     * Handles the payment process for an invoice.
     *
     * This method retrieves the invoice ID from the request input, validates it,
     * and fetches the corresponding invoice. It then loads all enabled payment
     * plugins, retrieves their payment options, and prepares the data to be
     * displayed in the payment view.
     *
     * @return void
     *
     * @throws \RuntimeException If a plugin's layout file or required method is missing.
     */
    public function payment()
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

        // Load enabled payment plugins
        $plugins = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-payment');
        $paymentOptions = [];

        foreach ($plugins as $plugin) {
            $params = new \Joomla\Registry\Registry($plugin->params);
            $pluginName = $params->get('display_name');

            $layoutPath = JPATH_PLUGINS . '/mothership-payment/' . $plugin->name . '/tmpl';
            if (!file_exists($layoutPath . '/instructions.php')) {
                throw new \RuntimeException("Layout file 'instructions.php' not found in path: $layoutPath");
            }

            $pluginInstance = $this->getPluginInstance($plugin->name);

            if (!method_exists($pluginInstance, 'initiate')) {
                throw new \RuntimeException("Plugin '{$plugin->name}' cannot be initiated.");
            }

            $fee_amount = $pluginInstance->getFee($invoice->total);
            $display_fee = $pluginInstance->displayFee($invoice->total);

            $layout = new FileLayout('instructions', $layoutPath);

            // Render the layout, passing data in an array
            $instructionsHtml = $layout->render([
                'invoiceId' => $id,
                'id' => null,
                'amount' => null,
                'payment_method' => null,
            ]);

            $paymentOptions[] = [
                'element' => $plugin->name,
                'name' => $pluginName,
                'fee_amount' => $fee_amount,
                'display_fee' => $display_fee,
                'instructions_html' => $instructionsHtml,
            ];
        }

        // Correct way to pass data to the view:
        $view = $this->getView('Invoice', 'html');
        $view->setModel($model, true);
        $view->item = $invoice;
        $view->paymentOptions = $paymentOptions;
        $view->setLayout('payment');
        $view->display();
    }

    /**
     * Retrieves an instance of a payment plugin by its name.
     *
     * This method normalizes the plugin name to lowercase, loads the 
     * 'mothership-payment' plugin group, and searches for the specified plugin.
     * If the plugin is found, it constructs the expected class name, verifies 
     * its existence, and instantiates the plugin class.
     *
     * @param string $pluginName The name of the payment plugin to retrieve.
     * 
     * @return object An instance of the specified payment plugin.
     * 
     * @throws \RuntimeException If the plugin class is not found or the plugin 
     *                           is not enabled.
     */
    protected function getPluginInstance(string $pluginName)
    {
        // Normalize plugin name casing
        $normalized = strtolower($pluginName);

        // Load the plugin group
        PluginHelper::importPlugin('mothership-payment');

        $plugins = PluginHelper::getPlugin('mothership-payment');

        foreach ($plugins as $plugin) {
            if ($plugin->name === $normalized) {
                // Build expected class name, e.g., PlgMothershippaymentPaypal
                $className = 'PlgMothershipPayment' . ucfirst($plugin->name);
       
                if (!class_exists($className)) {
                    throw new \RuntimeException("Plugin class '$className' not found.");
                }

                // Instantiate and return
                $dispatcher = Factory::getApplication()->getDispatcher();
                return new $className($dispatcher, (array) $plugin);
            }
        }

        throw new \RuntimeException("Payment plugin '$pluginName' not found or not enabled. ".json_encode($plugins));
    }

    /**
     * Processes a payment for an invoice.
     *
     * This method handles the payment process by validating input, creating payment records,
     * sending notifications, and invoking the appropriate payment plugin for further processing.
     *
     * @return void
     *
     * @throws \RuntimeException If the payment plugin cannot be initiated.
     */
    public function processPayment()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $invoiceId = $input->getCmd('id');
        $paymentMethod = $input->getCmd('payment_method');


        if (!$invoiceId || !$paymentMethod) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_REQUEST'), 'error');
            $this->setRedirect("index.php?option=com_mothership&view=invoice&id={$invoiceId}");
            return;
        }

        // Load the invoice
        $invoiceModel = $this->getModel('Invoice');
        $invoice = $invoiceModel->getItem($invoiceId);

        // Create the payment record
        $payment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('Payment', 'MothershipTable');
        $payment->client_id = $invoice->client_id;
        $payment->account_id = $invoice->account_id;
        $payment->amount = $invoice->total;
        $payment->status = 1; // Pending
        $payment->payment_method = $paymentMethod;
        $payment->payment_date = Factory::getDate()->toSql();
        $payment->created = Factory::getDate()->toSql();

        if (!$payment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED') . ' ' . $payment->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        $client = ClientHelper::getClient($payment->client_id);
        if (!$client) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_CLIENT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Get the company email from the extension settings
        $componentParams = Factory::getApplication()->getParams('com_mothership');
        $companyEmail = $componentParams->get('company_email');

        if (!$companyEmail) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_COMPANY_EMAIL_NOT_CONFIGURED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Create the invoice payment record
        $invoicePayment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('InvoicePayment', 'MothershipTable');
        $invoicePayment->invoice_id = $invoiceId;
        $invoicePayment->payment_id = $payment->id;
        $invoicePayment->applied_amount = $invoice->total;
        if (!$invoicePayment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoiceId, false));
            return;
        }

        // Send the admin an email notification
        EmailService::sendTemplate('payment.admin-pending', 
        $companyEmail, 
        "New Pending Payment for {$paymentMethod}", 
        [
            'admin_fname' => 'Trevor',
            'admin_email' => $companyEmail,
            'payment' => $payment,
            'invoice' => $invoice,
            'client' => $client,
            'confirm_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&task=payment.confirm&id={$payment->id}",
            'view_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&view=invoice&id={$invoiceId}",
        ]);

        // Log that the payment was initiated
        LogHelper::logPaymentInitiated(
            $invoiceId,
            $payment->id,
            $invoice->client_id,
            $invoice->account_id,
            $invoice->total,
            $paymentMethod
        );

        // Invoke the plugin to process
        try {
            $plugin = $this->getPluginInstance($paymentMethod);

            if (!method_exists($plugin, 'initiate')) {
                throw new \RuntimeException("Plugin '{$paymentMethod}' cannot be initiated.");
            }

            return $plugin->initiate($payment, $invoice); // Plugin handles redirect or rendering
        } catch (\Exception $e) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_PROCESSING_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=invoice&id={$invoiceId}&task=invoice.payment", false));
            return;
        }
    }

}
