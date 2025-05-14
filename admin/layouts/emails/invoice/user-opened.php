<?php
// This email should be triggered when the admin sets a draft invoice status to opened
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$invoice_number = $displayData['invoice']->number;
$client_name = $displayData['client']->name;
?>
<p>Hello <?php echo $fname; ?>,</p>
<p>Invoice <strong>#<?php echo $invoice_number; ?></strong> for <strong>`<?php echo $client_name; ?>`</strong> is ready for your review. Please sign to view
    details and make a payment. </p>