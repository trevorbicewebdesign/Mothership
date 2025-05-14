<?php
// This email should be triggered when the admin sets a draft invoice status to opened
defined('_JEXEC') or die;

$fname = $displayData['fname'];
$invoice = $displayData['invoice'];
$invoice_number = $invoice->number;
$client_name = $displayData['client']->name;

?>
<p>Hello <?php echo $fname; ?>,</p>
<p>Invoice <strong>#<?php echo $invoice_number; ?></strong> for <strong>`<?php echo $client_name; ?>`</strong> is ready for your review. Please sign in to view
    details and make a payment. </p>
<p>Please review, and settle the invoice before <strong><?php echo $invoice->due_date; ?></strong>.</p>