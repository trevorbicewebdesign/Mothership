<?php
// This email should be triggered when the admin sets a draft invoice status to opened
defined('_JEXEC') or die;

$fname = $displayData['fname'];

?>
<p>Hello <?= $fname; ?>,</p>
<p>You have a new invoice.</p>
