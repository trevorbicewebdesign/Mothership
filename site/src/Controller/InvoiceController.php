<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Mpdf\Mpdf;

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

        ob_start();
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render(['invoice' => $invoice]);
        $html = ob_get_clean();

        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output('Invoice-' . $invoice->number . '.pdf', 'I');

      
    }
}
