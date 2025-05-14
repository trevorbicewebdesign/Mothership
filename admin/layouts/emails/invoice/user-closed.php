<?php
// This email should be triggered to the user when the user's invoice is closed
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$invoice = $displayData['invoice'];
$client = $displayData['client'];
$account = $displayData['account'];
?>
<p>Hello <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>,</p>
<p>Thank you for your payment.<p>
<p>Invoice #<?php echo $invoice->number; ?> for Account `<?php echo $account->name; ?>` for <?php echo $invoice->total; ?> has been marked as closed.</p>
