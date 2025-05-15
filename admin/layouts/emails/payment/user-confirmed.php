<?php
// This email should be triggered to the user when the user's payment is confirmed.
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$payment = $displayData['payment'];
?>
<p>Hello <?php echo $fname; ?>,</p>
<p>Thank You!</p>
<p>Your payment <?php echo "#".$payment->id; ?> for <?php echo "$".number_format($payment->amount, 2); ?> with payment method `<?php echo $payment->payment_method; ?>` has been received and confirmed by an admin.</p>