<?php
// This email should be triggered when the admin sets a draft invoice status to opened
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$invoice_number = $displayData['invoice']['invoice_number'];
$client_name = $displayData['client']['client_name'];
?>
<p>Hello <?= $fname; ?>,</p>
<p>Invoice #<?php echo $invoice_number; ?> for <?php echo $client_name; ?> is ready for your review. Please sign to view
    details and make a payment. </p>