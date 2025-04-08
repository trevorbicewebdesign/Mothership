<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
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

        // Correct way to pass data to the view:
        $view = $this->getView('Payment', 'html');
        $view->setModel($model, true);
        $view->item = $payment;
        $view->setLayout('thank-you');
        $view->display();
    }

    public function downloadPdf()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

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

        // Generate the HTML
        $layout = new FileLayout('pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        $html = $layout->render(['payment' => $payment]);

        // Turn off Joomla's output
        ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Payment-' . $payment->number . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Generate and output the PDF
        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output(null, 'I');

        $app->close();
    }

    public function payment()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

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

        // Load enabled payment plugins
        $plugins = \Joomla\CMS\Plugin\PluginHelper::getPlugin('mothership-payment');
        $paymentOptions = [];

        foreach ($plugins as $plugin) {
            $params = new \Joomla\Registry\Registry($plugin->params);
            $pluginName = $params->get('display_name') ?: ucfirst(str_replace('mothership-', '', $plugin->element));

            $paymentOptions[] = [
                'element'     => $plugin->name,
                'name'        => $pluginName,
                'fee_amount'  => $plugin->getFee($invoice->total),
                'display_fee' => $plugin->displayFee($invoice->total),
            ];
        }

        // Correct way to pass data to the view:
        $view = $this->getView('Payment', 'html');
        $view->setModel($model, true);
        $view->item = $payment;
        $view->paymentOptions = $paymentOptions;
        $view->setLayout('payment');
        $view->display();
    }

    public function processPayment()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');
        $paymentMethod = $input->getCmd('payment_method');

        if (!$id || !$paymentMethod) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_REQUEST'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&task=payment.payment&id=' . $id, false));
            return;
        }

        $model = $this->getModel('Payment');
        $payment = $model->getItem($id);

        if (!$payment) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }

        // Set the payment method
        $payment->payment_method = $paymentMethod;

        $paymentData = [
            'payment_method' => $paymentMethod,
        ];

        // Import the payment plugins BEFORE dispatching the event
        \Joomla\CMS\Plugin\PluginHelper::importPlugin('mothership-payment');

        $dispatcher = Factory::getApplication()->getDispatcher();
        $event = new Event('onMothershipPaymentRequest', ['payment' => $payment, 'paymentData' => $paymentData]);
        
        $results = $dispatcher->dispatch('onMothershipPaymentRequest', $event);

        if (!empty($results)) {
    
            $arguments = $event->getArguments();
            foreach ($arguments['result'] as $result) {
                if ($result['status'] === 'redirect') {
                    $this->setRedirect($result['url']);
                    return;
                }
            }
        }

        $app->enqueueMessage(Text::_(sprintf('COM_MOTHERSHIP_NO_PAYMENT_HANDLER', $paymentMethod)), 'danger');
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
    }

}
