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
use Joomla\CMS\Layout\FileLayout;
use Joomla\Database\DatabaseDriver;
use TrevorBice\Component\Mothership\Administrator\Helper\PaymentHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

class PlgMothershipPaymentPaybycheck extends CMSPlugin
{
    protected $autoloadLanguage = true;

    
    public function initiate($payment, $invoice)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $invoiceId = $input->getInt('id', 0);
        // $amount = $input->getFloat('amount', 0.0); // Retrieve the amount from the input
        // echo 'index.php?option=com_mothership&view=payment&task=zelle.displayInstructions&invoice_id=' . $invoiceId . '&amount=' . $amount;
        // die();

        if ($invoiceId) {
            // Redirect to the `Pay By Check` instructions page with the invoice ID and amount
            $paymentLink = Route::_("index.php?option=com_mothership&controller=payment&task=pluginTask&plugin=paybycheck&action=displayInstructions&invoice_id={$invoiceId}", false);
            Factory::getApplication()->redirect($paymentLink);
        } else {
            // Handle error: invalid invoice ID or amount
            // Log::add('Invalid invoice ID or amount for Zelle payment.', 'error', 'jerror');
            return false;
        }
    }

    
    public function displayInstructions()
    {
        // Load the Joomla application and input objects
        $app = Factory::getApplication();
        $input = $app->getInput();
        $invoiceId = $input->getInt('invoice_id', 0);
        $amount = $input->getFloat('amount', 0.0);
        $id = $input->getInt('id', 0);

        $payment = PaymentHelper::getPayment($id);

        if ($invoiceId) {
            // Load the `Pay By Check` instructions layout
            $layoutPath = __DIR__ . '/tmpl'; // plugin folder/tmpl
            $layout = new FileLayout('instructions', $layoutPath);

            // Render the layout, passing data in an array
            echo $layout->render([
                'invoiceId' => $invoiceId,
                'id' => $id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
            ]);
        } else {
            // Handle error: invalid invoice ID or amount
            // Log::add('Invalid invoice ID or amount for `Pay By Check` payment.', 'error', 'jerror');
            return false;
        }
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
        $base_fee = 0.30;
        $percentage_fee = 3.9;
        $fee_total = $base_fee + ($amount * ($percentage_fee / 100));
        return number_format($fee_total, 2, '.', '');
    }

    /**
     * Return a human-readable string displaying the fee structure and the calculated fee.
     *
     * @param   float  $amount  The payment amount.
     *
     * @return  string  A formatted message showing the fee breakdown and the total fee.
     */
    public function displayFee($amount)
    {
        $calculatedFee = $this->getFee($amount);
        return "Fee: 3.9% + \$0.30 = \$" . $calculatedFee;
    }

}
