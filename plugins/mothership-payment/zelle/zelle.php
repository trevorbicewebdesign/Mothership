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

class PlgMothershipPaymentZelle extends CMSPlugin
{
    protected $autoloadLanguage = true;

    /**
     * Intercept request after routing. If "zelleinstructions=1", display our custom HTML.
     */
    public function onAfterRoute()
    {
        $app     = Factory::getApplication();
        $option  = $app->input->getCmd('option');
        $isZelle = $app->input->getCmd('zelleinstructions', '0');

        // Only act on front-end requests to com_mothership with &zelleinstructions=1
        if ($app->isClient('site') && $option === 'com_mothership' && $isZelle === '1')
        {
            // Gather data as needed
            $invoiceId = $app->input->getInt('invoice_id', 0);

            // Use Joomla's FileLayout to load the layout from /tmpl/zelleinstructions.php
            $layoutPath = __DIR__ . '/tmpl'; // plugin folder/tmpl
            $layout     = new FileLayout('zelleinstructions', $layoutPath);

            // Render the layout, passing data in an array
            $html = $layout->render(['invoiceId' => $invoiceId]);

            // Output the HTML
            echo $html;

            // End the request so Joomla doesn't continue
            $app->close();
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
        // Only handle if this is the selected payment method
        if ($invoice->payment_method !== 'zelle') {
            return null;
        }

        $paymentLink = "https://joomlav4.trevorbice.com/?option=com_mothership&view=&invoices";

        InvoiceHelper::updateInvoiceStatus($invoice->id, 1);

        PaymentHelper::insertPaymentRecord(
            $invoice->client_id,
            $invoice->account_id,
            $invoice->total,
            date("Y-m-d H:i:s"),
            0,
            false,
            'PayPal',
            0,
            2,
        );

        return [
            'status'  => 'redirect',
            'url'     => $paymentLink,
        ];
    }

}
