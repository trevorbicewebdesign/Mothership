<?php
// This email should be triggered to the user when the user's invoice is closed
defined('_JEXEC') or die;

$fname = $displayData['fname'];

?>
<p>Hello <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>,</p>
<p>Your invoice has been marked as closed.</p>
