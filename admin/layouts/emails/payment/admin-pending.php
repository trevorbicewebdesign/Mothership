<?php
$admin_fname   = $displayData['admin_fname'];
$payment_id    = $displayData['payment_id'];
$payment_method = $displayData['payment_method'];
$payment_amount = $displayData['payment_amount'];
$payment_date   = $displayData['payment_date'] ?? null;

$client_name   = $displayData['client_name'] ?? null;
$account_name  = $displayData['account_name'] ?? null;
$project_name  = $displayData['project_name'] ?? null;
$invoice_number = $displayData['invoice_number'] ?? null;

$confirm_link  = $displayData['confirm_link'] ?? '#';
$view_link     = $displayData['view_link'] ?? '#';
?>
<p>Hello <?= $admin_fname; ?>,</p>
<p>A new payment is pending your confirmation.</p>
