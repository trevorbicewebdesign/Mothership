<?php
defined('_JEXEC') or die;

$admin_fname   = $displayData['admin_fname'];

$payment = $displayData['payment'];
$invoice = $displayData['invoice'];
$client = $displayData['client'];

$view_link = $displayData['view_link'] ?? '#';
?>
<p>Hello <?= $admin_fname; ?>,</p>

<p>A new <strong><?php echo $payment->payment_method; ?></strong> payment has been initiated by <strong><?php echo $client->name; ?></strong> for the amount of <strong><?php echo "$".number_format($invoice->amount, 2); ?></strong>.</p>

<p>Please mark this payment as <code>Confirmed</code> once you have received and verified the check.</p>

<p><a href="<?php echo $view_link; ?>" style="display:inline-block;padding:10px 15px;background:#007BFF;color:#fff;text-decoration:none;border-radius:4px;">Review Payment</a></p>

<p>Thank you,<br>Mothership Billing System</p>

