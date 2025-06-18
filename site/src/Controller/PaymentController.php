<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; // Import LogHelper
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Mpdf\Mpdf;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;

// Load all enabled payment plugins
PluginHelper::importPlugin('mothership-payment');

class PaymentController extends BaseController
{    
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'payment'));
        parent::display($cachable, $urlparams);
    }
    
    public function thankyou()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        
        $id = $input->getInt('id');
        $invoice_id = $input->getInt('invoice_id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        $model = $this->getModel('Payment');
        $payment = $model->getItem($id);

        if (!$payment) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        // Redirect to the thank you page layout with the correct payment id and invoice id
        $this->setRedirect(Route::_("index.php?option=com_mothership&view=payment&layout=thank-you&id={$payment->id}&invoice_id={$invoice_id}", false));
    }

    public function cancel()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        // Redirect to the cancel page layout with the correct payment id and invoice id
        $this->setRedirect(Route::_("index.php?option=com_mothership&view=payment&layout=cancel&id={$id}", false));
    }
}
