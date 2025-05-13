<?php
$admin_fname   = $displayData['admin_fname'];

$confirm_link  = $displayData['confirm_link'] ?? '#';
$view_link     = $displayData['view_link'] ?? '#';
?>
<p>Hello <?= $admin_fname; ?>,</p>
<p>A new payment is pending your confirmation.</p>
<p><?php echo $displayData['invoice']->amount; ?></p>
