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
    
    public function pluginTask()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
    
        $pluginName = $input->getCmd('plugin');
        $action     = $input->getCmd('action');
    
        if (!$pluginName || !$action) {
            throw new \RuntimeException('Missing plugin or action.');
        }
    
        try {
            $plugin = $this->getPluginInstance($pluginName);
    
            if (!method_exists($plugin, $action)) {
                throw new \RuntimeException("Plugin method '$action' not found in '$pluginName'");
            }
    
            return $plugin->$action();
        } catch (\Exception $e) {
            $app->enqueueMessage("Plugin error: " . $e->getMessage(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=payments', false));
            return;
        }
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

        throw new \RuntimeException("Payment plugin '$pluginName' not found or not enabled. 1 ".json_encode($plugins));
    }
}
