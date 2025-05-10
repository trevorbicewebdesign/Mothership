<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.PayByCheck
 * @copyright   ...
 * @license     ...
 */

defined('_JEXEC') or die;

$plugin = JPluginHelper::getPlugin('mothership-payment', 'paybycheck');
$pluginParams = new JRegistry($plugin->params);

/** @var array $displayData */
$invoiceId = (int) ($displayData['invoiceId'] ?? 0);
$paymentId = (int) ($displayData['id'] ?? 0);
?>

<h1>Pay By Check Payment Instructions</h1>

<p><strong>Invoice #<?= $invoiceId ?></strong></p>

<p>
  Please make your check payable to:  
  <br><strong><?= htmlspecialchars($pluginParams['checkpayee']); ?></strong>
</p>

<p>
  Be sure to include the invoice number <strong>#<?= $invoiceId ?></strong> in the memo line of the check.
</p>

<p>
  Once your check has been mailed, please click the button below to let us know.  
  Your payment status will be marked as <code>Pending</code> until an administrator confirms receipt.
</p>

<p>
  <a href="<?= JRoute::_('index.php?option=com_mothership&view=payments'); ?>">View All Payments</a>
</p>

<p>
  <a href="<?= JRoute::_("index.php?option=com_mothership&task=payment.thankyou&id={$paymentId}&invoice_id={$invoiceId}"); ?>" class="btn btn-primary">
    I've Sent the Payment
  </a>
</p>
