<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.PayPal
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri as JUri;
use Joomla\Database\DatabaseDriver;
// use Mothership Helper
use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

class PlgMothershipPaymentPaypal extends CMSPlugin
{

    protected $autoloadLanguage = true;

    public function onAfterInitialiseMothership()
    {
        $app = Factory::getApplication();

        // Process only if this is a PayPal IPN request.
        if ($app->input->getCmd('paypal_notify') !== '1') {
            return;
        }

        require_once JPATH_ROOT . '/plugins/mothership-payment/paypal/assets/vendor/autoload.php';

        $this->processNotify();

    }

    /**
     * Handle payment requests from Mothership.
     *
     * @param   object  $invoice      The invoice object (from Mothership)
     * @param   array   $paymentData  Any relevant payment data
     *
     * @return  array|null  Result of the payment process or null if not handled
     */
    public function onMothershipPaymentRequest($invoice, $paymentData)
    {
        // Only handle if this is PayPal
        if ($invoice->payment_method !== 'paypal') {
            return null;
        }

        // Construct final payment link
        $paymentLink = $this->getPaypalRedirectUrl((int) $invoice->id, (float) $invoice->total);

        return [
            'status' => 'redirect',
            'url' => $paymentLink,
        ];
    }


    /**
     * Calculate the processing fee based on the payment amount.
     *
     * @param   float  $amount  The payment amount.
     *
     * @return  string  The calculated fee formatted to two decimals.
     */
    public function getFee($amount)
    {
        $base_fee = 0.49;
        $percentage_fee = 3.63;
        $fee_total = $base_fee + ($amount * ($percentage_fee / 100));
        return number_format($fee_total, 2, '.', '');
    }

    /**
     * Return a human-readable string displaying the fee structure and the calculated fee.
     *
     * @param   float  $amount  The payment amount.
     *
     * @return  string  A formatted message showing the fee breakdown and the otal  .     */
    public function displayFee($amount)
    {
        $calculatedFee = $this->getFee($amount);
        return "Fee: 3.9% + \$0.30 = \$" . $calculatedFee;
    }

    public function getDomain()
    {
        return $_SERVER['HTTP_ORIGIN'] . "/";
    }

    public function getParam($param_name, $default = null)
    {
        return $this->params->get($param_name, $default);
    }

    /**
     * Generates the PayPal redirect URL for a given invoice.
     *
     * @param int $invoiceId The ID of the invoice.
     * @param float $amount The amount to be paid.
     * @return string The PayPal redirect URL.
     */
    public function getPaypalRedirectUrl(int $invoice_id, float $amount)
    {
        $domain = $this->getDomain();
        if (empty($domain)) {
            throw new Exception('Invalid domain');
        }
        if (empty($amount) || $amount == 0) {
            throw new Exception('Invalid amount');
        }
        try {
            $invoice = InvoiceHelper::getInvoice($invoice_id);
        } catch (Exception $e) {
            // getInvoice will throw an exception if the invoice ID is invalid
            // let that exception bubble up
            throw $e;
        }
        $amount = number_format(($amount + $this->getFee($amount)), 2, '.', '');

        $paypalUrl = $this->getParam('sandbox', 0)
            ? "https://www.sandbox.paypal.com/cgi-bin/webscr?"
            : "https://www.paypal.com/cgi-bin/webscr?";
        $business = $this->getParam('paypal_email');

        $paypalData = [
            'cmd' => '_xclick',
            'business' => $business,
            'custom' => $invoice_id,
            'item_name' => "Invoice {$invoice->number}",
            'amount' => $amount,
            'currency_code' => 'USD',
            'no_shipping' => 1,
            'cancel_return' => "{$domain}index.php?option=com_mothership&view=invoices",
            'notify_url' => "{$domain}index.php?option=com_mothership&paypal_notify=1&invoice={$invoice_id}",
            'return' => "{$domain}index.php?option=com_mothership&view=invoices",
        ];

        return $paypalUrl . http_build_query($paypalData);
    }

    private function verifyPaypalIPN($postData)
    {
        $paypalUrl = $this->params->get('sandbox', 0)
            ? "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr"
            : "https://ipnpb.paypal.com/cgi-bin/webscr";

        $ch = curl_init($paypalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function process()
    {
        $task = Factory::getApplication()->input->getCmd('task', '');
        switch ($task) {
            case 'notify':
                $this->processNotify();
                break;
        }
    }

    public function processNotify()
    {
        $app = Factory::getApplication();
        $rawPostData = $_POST;
        // log the IPN data
        Log::addLogger(
            [
                'text_file' => 'paypal_ipn.php',
                'extension' => 'plg_mothership_payment_paypal'
            ],
            Log::INFO,
            ['paypal_ipn']
        );

        $veryifyIPN = $this->verifyPaypalIPN($rawPostData);

        // Extract required PayPal variables
        $txn_id = $app->input->getString('txn_id', '');
        $custom = $app->input->getString('custom', '');
        $payment_status = $app->input->getString('payment_status', '');
        $invoice_id = $app->input->getInt('invoice', 0);
        $mc_gross = $app->input->getFloat('mc_gross', 0);
        $mc_fee = $app->input->getFloat('mc_fee', 0);
        
        switch ($payment_status) {
            case 'Completed':
                try {
                    $invoice = InvoiceHelper::getInvoice($invoice_id);
                } catch (Exception $e) {
                    // let the errors bubble up
                    throw new \RuntimeException($e->getMessage());
                }

                $client_id = $invoice->client_id;
                $account_id = $invoice->account_id;

                // Set the invoice to be paid
                InvoiceHelper::setInvoiceClosed($invoice_id);

                // Use the PaymentHelper to record the payment and invoice mapping
                $payment_method = "paypal";
                $status = 2;

                
                try {
                    $payment_id = PaymentHelper::insertPaymentRecord(
                        $client_id,
                        $account_id,
                        $mc_gross,
                        date("Y-m-d H:i:s"),
                        $mc_fee,
                        false,
                        $payment_method,
                        $txn_id,
                        $status
                    );

                    $invoicePaymentId = PaymentHelper::insertInvoicePayments($invoice_id, $payment_id, $mc_gross);
                    if (!$invoicePaymentId) {
                        throw new \RuntimeException("insertInvoicePayments() failed");
                    }

                } catch (\Exception $e) {
                    // log the error
                    echo "Failed to store payment:" . $e->getMessage();
                    exit();
                
                }

                exit('IPN Received');
                break;
            case 'Pending':
                exit('Payment pending');
            case 'Failed':
                Log::add("Payment failed: $payment_status", Log::WARNING, 'paypal_ipn');
                exit('Payment failed');
            default:
                Log::add("Payment not completed: $payment_status", Log::WARNING, 'paypal_ipn');
                exit('Payment not completed');
        }
    }
}
