<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Mpdf\Mpdf;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;

class InvoiceController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'invoice'));
        parent::display($cachable, $urlparams);
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

        // Generate the HTML
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        $html = $layout->render(['invoice' => $invoice]);

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

    public function pay()
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
            $pluginName = $params->get('display_name') ?: ucfirst(str_replace('mothership-', '', $plugin->element));

            $paymentOptions[] = [
                'element'     => $plugin->element,
                'name'        => $pluginName,
                'fee_percent' => (float) $params->get('fee_percent', 0),
                'fee_fixed'   => (float) $params->get('fee_fixed', 0),
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


}
