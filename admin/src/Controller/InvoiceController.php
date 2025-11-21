<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Joomla\CMS\Layout\FileLayout;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use Joomla\CMS\Plugin\PluginHelper;




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

    /**
     * Retrieves an instance of an invoice PDF plugin by its name.
     *
     * This method normalizes the plugin name to lowercase, loads the 
     * 'mothership-invoice-pdf' plugin group, and searches for the specified plugin.
     * If the plugin is found, it constructs the expected class name, verifies 
     * its existence, and instantiates the plugin class.
     *
     * @param string $pluginName The name of the invoice PDF plugin to retrieve.
     * 
     * @return object An instance of the specified invoice PDF plugin.
     * 
     * @throws \RuntimeException If the plugin class is not found or the plugin 
     *                           is not enabled.
     */
    protected function getPluginInstance(string $pluginName)
    {
        $normalized = strtolower($pluginName);

        \Joomla\CMS\Plugin\PluginHelper::importPlugin('mothership-invoice-pdf');

        $plugins = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-invoice-pdf');

        foreach ($plugins as $plugin) {
            if ($plugin->name === $normalized) {
                // Expected class name e.g. PlgMothershipInvoicePdfTbwebdesign
                $className = 'PlgMothershipInvoicePdf' . ucfirst($plugin->name);

                if (!class_exists($className)) {
                    throw new \RuntimeException("Plugin class '$className' not found.");
                }

                $dispatcher = \Joomla\CMS\Factory::getApplication()->getDispatcher();

                return new $className($dispatcher, (array) $plugin);
            }
        }

        throw new \RuntimeException(
            "Invoice PDF plugin '$pluginName' not found or not enabled."
        );
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

        $client = ClientHelper::getClient($invoice->client_id);
        $account = AccountHelper::getAccount($invoice->account_id);
        $business = MothershipHelper::getMothershipOptions();

        // Data passed to either plugin or layout
        $viewData = [
            'invoice' => $invoice,
            'client' => $client,
            'account' => $account,
            'business' => $business,
        ];

        $html = null;

        // Try invoice-PDF plugin first (if configured)

        try {
            $pluginName = $client->invoice_pdf_template;

            if ($pluginName !== '') {
                $plugin = $this->getPluginInstance($pluginName);

                if (!method_exists($plugin, 'renderInvoicePdf')) {
                    throw new \RuntimeException(
                        "Invoice PDF plugin '{$pluginName}' does not implement renderInvoicePdf()."
                    );
                }

                // Expected to return HTML string
                $html = $plugin->renderInvoicePdf($viewData);
            }
        } catch (\Throwable $e) {
            // Soft-fail: log / warn, but continue to fallback layout
            $app->enqueueMessage(
                Text::sprintf('COM_MOTHERSHIP_INVOICE_PDF_PLUGIN_FAILED', $e->getMessage()),
                'warning'
            );
            $html = null;
        }

        // Fallback to default internal layout if no plugin, plugin failed, or plugin returned nothing
        if (empty($html)) {
            $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
            $html = $layout->render($viewData);
        }

        echo $html;
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

        $client = ClientHelper::getClient($invoice->client_id);
        $account = AccountHelper::getAccount($invoice->account_id);
        $business = MothershipHelper::getMothershipOptions();

        // Data passed to either plugin or layout
        $viewData = [
            'invoice' => $invoice,
            'client'  => $client,
            'account' => $account,
            'business'=> $business,
        ];

        $html = null;

        // Try invoice-PDF plugin first (if configured) — match preview behavior
        try {
            $pluginName = $client->invoice_pdf_template;

            if ($pluginName !== '') {
                $plugin = $this->getPluginInstance($pluginName);

                if (!method_exists($plugin, 'renderInvoicePdf')) {
                    throw new \RuntimeException(
                        "Invoice PDF plugin '{$pluginName}' does not implement renderInvoicePdf()."
                    );
                }

                // Expected to return HTML string
                $result = $plugin->renderInvoicePdf($viewData);

                // If plugin returned HTML, use it. Otherwise fall back to layout.
                if (is_string($result) && trim($result) !== '') {
                    $html = $result;
                } else {
                    // allow plugin to have soft-failed by returning nothing/invalid content
                    $html = null;
                }
            }
        } catch (\Throwable $e) {
            // Soft-fail: log / warn, but continue to fallback layout
            $app->enqueueMessage(
                Text::sprintf('COM_MOTHERSHIP_INVOICE_PDF_PLUGIN_FAILED', $e->getMessage()),
                'warning'
            );
            $html = null;
        }

        // Fallback to default internal layout if no plugin, plugin failed, or plugin returned nothing
        if (empty($html)) {
            $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
            $html = $layout->render($viewData);
        }

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Generate PDF from the resolved HTML
        $pdf = new Mpdf([
            'mode'     => 'utf-8',
            'format'   => 'Letter',
            'orientation' => 'P',
            'dpi'      => 72,
            'img_dpi'  => 72,
            
        ]);

        

        $pdf->SetHTMLFooter('
            <div class="final-company-info" style="text-align:center; font-size:10px;line-height:1em;">
                <strong>' . htmlspecialchars($business['company_name']) . '</strong><br>
                ' . htmlspecialchars($business['company_address_1']) . '<br>
                ' . htmlspecialchars($business['company_city']) . ', ' . 
                    htmlspecialchars($business['company_state']) . ' ' . 
                    htmlspecialchars($business['company_zip']) . '<br>
                ' . htmlspecialchars($business['company_phone']) . '
            </div>
            ');

        $pdf->WriteHTML($html);

        // Output inline (I) to match previous behavior; change to 'D' to force download
        $filename = 'Invoice-' . (!empty($invoice->number) ? $invoice->number : $invoice->id) . '.pdf';
        $pdf->Output($filename, 'I');

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