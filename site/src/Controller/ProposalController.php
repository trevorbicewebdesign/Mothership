<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseDriver;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Administrator\Service\EmailService;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProposalHelper;
use Mpdf\Mpdf;

// Add missing imports
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Load all enabled payment plugins
PluginHelper::importPlugin('mothership-payment');

class ProposalController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'proposal'));
        parent::display($cachable, $urlparams);
    }

    public function approveConfirm(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        // CSRF check
        if (!Session::checkToken('post')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        try {
            $proposal = ProposalHelper::getProposal($id);

            // Idempotency / safety
            if ((int) $proposal->status === 3) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_ALREADY_APPROVED'), 'info');
                $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $id, false));
                return;
            }

            if ((int) $proposal->status === 4) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_ALREADY_DECLINED'), 'warning');
                $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $id, false));
                return;
            }

            // Approved = 3
            ProposalHelper::updateProposalStatus($proposal, 3);

            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_APPROVED_SUCCESS'), 'success');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $id, false));
            return;
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_ERROR_PROPOSAL_UPDATE_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&layout=approve&id=' . $id, false));
            return;
        }
    }

    public function denyConfirm(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        // CSRF check
        if (!Session::checkToken('post')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        try {
            $proposal = ProposalHelper::getProposal($id);

            // Idempotency / safety
            if ((int) $proposal->status === 4) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_ALREADY_DECLINED'), 'info');
                $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $id, false));
                return;
            }

            if ((int) $proposal->status === 3) {
                $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_ALREADY_APPROVED'), 'warning');
                $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $id, false));
                return;
            }

            // Declined = 4
            ProposalHelper::updateProposalStatus($proposal, 4);

            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PROPOSAL_DECLINED_SUCCESS'), 'success');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        } catch (\Throwable $e) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_ERROR_PROPOSAL_UPDATE_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&layout=approve&id=' . $id, false));
            return;
        }
    }

    /**
     * Handles the download of an proposal as a PDF file.
     *
     * This method retrieves an proposal by its ID, generates a PDF representation
     * of the proposal, and streams it to the browser for download or inline viewing.
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
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        $proposal = $model->getItem($id);
        $client = ClientHelper::getClient($proposal->client_id);
        $account = AccountHelper::getAccount($proposal->account_id);
        $business = MothershipHelper::getMothershipOptions();

        if (!$proposal) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PROPOSAL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        ob_start();
        $layout = new FileLayout('proposal-pdf', JPATH_ROOT . '/components/com_mothership/layouts');
        echo $layout->render([
            'proposal' => $proposal,
            'client' => $client,
            'account' => $account,
            'business' => $business
        ]);
        $html = ob_get_clean();

        $pdf = new Mpdf();
        $pdf->WriteHTML($html);
        $pdf->Output(null, 'I');

        $app->close();
    }


    public function approve()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PROPOSAL_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        $model = $this->getModel('Proposal');
        $proposal = $model->getItem($id);

        if (!$proposal) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PROPOSAL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposals', false));
            return;
        }

        // Correct way to pass data to the view:
        $view = $this->getView('Proposal', 'html');
        $view->setModel($model, true);
        $view->item = $proposal;
        $view->setLayout('approve');
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

        throw new \RuntimeException("Payment plugin '$pluginName' not found or not enabled. " . json_encode($plugins));
    }

    /**
     * Processes a payment for an proposal.
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

        $proposalId = $input->getCmd('id');
        $paymentMethod = $input->getCmd('payment_method');

        if (!$proposalId || !$paymentMethod) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_INVALID_PAYMENT_REQUEST'), 'error');
            $this->setRedirect("index.php?option=com_mothership&view=proposal&id={$proposalId}");
            return;
        }

        // Load the proposal
        $proposalModel = $this->getModel('Proposal');
        $proposal = $proposalModel->getItem($proposalId);

        // Create the payment record
        $payment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('Payment', 'MothershipTable');
        $payment->client_id = $proposal->client_id;
        $payment->account_id = $proposal->account_id;
        $payment->amount = $proposal->total;
        $payment->status = 1; // Pending
        $payment->payment_method = $paymentMethod;
        $payment->payment_date = Factory::getDate()->toSql();
        $payment->created = Factory::getDate()->toSql();

        if (!$payment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED') . ' ' . $payment->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposalId, false));
            return;
        }

        $client = ClientHelper::getClient($payment->client_id);
        if (!$client) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_CLIENT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposalId, false));
            return;
        }

        // Get the company email from the extension settings
        $componentParams = Factory::getApplication()->getParams('com_mothership');
        $companyEmail = $componentParams->get('company_email');

        if (!$companyEmail) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_COMPANY_EMAIL_NOT_CONFIGURED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposalId, false));
            return;
        }

        // Create the proposal payment record
        $proposalPayment = Factory::getApplication()
            ->bootComponent('com_mothership')
            ->getMVCFactory()
            ->createTable('ProposalPayment', 'MothershipTable');
        $proposalPayment->proposal_id = $proposalId;
        $proposalPayment->payment_id = $payment->id;
        $proposalPayment->applied_amount = $proposal->total;
        if (!$proposalPayment->store()) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_SAVE_FAILED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposalId, false));
            return;
        }

        // Send the admin an email notification
        EmailService::sendTemplate(
            'payment.admin-pending',
            $companyEmail,
            "New Pending Payment for {$paymentMethod}",
            [
                'admin_fname' => 'Trevor',
                'admin_email' => $companyEmail,
                'payment' => $payment,
                'proposal' => $proposal,
                'client' => $client,
                'confirm_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&task=payment.confirm&id={$payment->id}",
                'view_link' => "http://localhost:8080/administrator/index.php?option=com_mothership&view=proposal&id={$proposalId}",
            ]
        );

        // Log that the payment was initiated
        LogHelper::logPaymentInitiated(
            $proposalId,
            $payment->id,
            $proposal->client_id,
            $proposal->account_id,
            $proposal->total,
            $paymentMethod
        );

        // Invoke the plugin to process
        try {
            $plugin = $this->getPluginInstance($paymentMethod);

            if (!method_exists($plugin, 'initiate')) {
                throw new \RuntimeException("Plugin '{$paymentMethod}' cannot be initiated.");
            }

            return $plugin->initiate($payment, $proposal); // Plugin handles redirect or rendering
        } catch (\Exception $e) {
            $app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_PAYMENT_PROCESSING_FAILED', $e->getMessage()), 'error');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=proposal&id={$proposalId}&task=proposal.payment", false));
            return;
        }
    }


}
