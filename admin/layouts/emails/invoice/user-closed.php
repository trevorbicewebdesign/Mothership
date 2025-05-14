<?php
// This email should be triggered to the user when the user's invoice is closed
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$invoice = $displayData['invoice'];
$client = $displayData['client'];

?>
<p>Hello <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>,</p>
<p>Thank you for your payment, invoice #<?php echo $invoice->number; ?> has been marked as closed.</p>
