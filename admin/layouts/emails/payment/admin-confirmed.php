<?php
// This email should be triggered to an admin when a payment is confirmed.
defined('_JEXEC') or die;

$admin_fname = $displayData['admin_fname'];
$payment = $displayData['payment'];
?>
<p>Hello <?= $admin_fname; ?>,</p>
<p>A payment has been confirmed.</p>