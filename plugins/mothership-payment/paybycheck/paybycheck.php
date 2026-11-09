<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.PayByCheck
 * @copyright   (C) 2025 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

class PlgMothershipPaymentPaybycheck extends CMSPlugin
{
    protected $autoloadLanguage = true;

    public function initiate($payment, $invoice):bool
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        // The invoice comes from the caller; the request id is only a fallback.
        $invoiceId = (int) ($invoice->id ?? 0) ?: $input->getInt('id', 0);
        if ($invoiceId) {
            $paymentLink = Route::_("index.php?option=com_mothership&controller=payment&task=payment.thankyou&id={$payment->id}&invoice_id={$invoiceId}", false);
            Factory::getApplication()->redirect($paymentLink);
            return true;
        } else {
            return false;
        }
    }

    public function getFee($amount): float
    {
        // Pay by Check does not charge any fees, so the fee is always 0.
        return 0.0;
    }

    public function displayFee($amount):string
    {
        return "No Fee";
    }
}
