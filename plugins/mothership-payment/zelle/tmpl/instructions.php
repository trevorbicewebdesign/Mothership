<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.Zelle
 * @copyright   ...
 * @license     ...
 */

// Protect against direct access
defined('_JEXEC') or die;

// get the plugin settings
$plugin = JPluginHelper::getPlugin('mothership-payment', 'zelle');
$pluginParams = new JRegistry($plugin->params);

/** @var array $displayData */
$invoiceId = $displayData['invoiceId'] ?? 0;
?>
<h1>Zelle Payment Instructions</h1>
<p>Invoice ID: <?php echo (int) $invoiceId; ?></p>
<p>
  Please send payment via Zelle to <strong><?php echo $pluginParams['zelle_email']; ?></strong>.
</p>
<p>
  After sending the payment please visit <a href="<?php echo JRoute::_('index.php?option=com_mothership&view=payments'); ?>">Payments</a><br/>
  Your payment will have been set to `pending` until an administrator receives payment and can set the payment to `completed`.<br/>
  <button type="button" onclick="alert('Payment Sent')">Payment Sent</button>
</p>
