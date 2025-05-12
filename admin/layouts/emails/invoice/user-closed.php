<?php
$fname = $displayData['fname'];
?>
<p>Hello <?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>,</p>
<p>Your invoice has been marked as closed.</p>
