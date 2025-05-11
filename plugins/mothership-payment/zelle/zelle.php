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

    public function initiate($payment, $invoice)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $invoiceId = $input->getInt('id', 0);
        if ($invoiceId) {
            $paymentLink = Route::_("index.php?option=com_mothership&controller=payment&task=payment.thankyou&id={$payment->id}&invoice_id={$invoiceId}", false);
            Factory::getApplication()->redirect($paymentLink);
        } else {
            return false;
        }
    }

}
