<?php
// This email should be triggered to an admin when a payment is confirmed.
defined('_JEXEC') or die;

$admin_fname = $displayData['admin_fname'];
$payment = $displayData['payment'];
$client = $displayData['client'];
?>
<p>Hello <?php echo $admin_fname; ?>,</p>
<p>`<?php echo $client->name; ?>` payment <?php echo "#".$payment->id; ?> for <?php echo "$".number_format($payment->amount, 2); ?> with payment method `<?php echo $payment->payment_method; ?>` has been received and confirmed by an admin.</p>