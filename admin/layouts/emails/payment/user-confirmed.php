<?php
// This email should be triggered to the user when the user's payment is confirmed.
defined('_JEXEC') or die;

$fname = $displayData['fname'];
?>
<p>Hello <?= $fname; ?>,</p>
<p>Your payment has been confirmed.</p>
