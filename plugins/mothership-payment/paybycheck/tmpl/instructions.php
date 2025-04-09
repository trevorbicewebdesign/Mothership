<?php
/**
 * @package     Mothership
 * @subpackage  Plugin.Mothership-Payment.Zelle
 * @copyright   ...
 * @license     ...
 */

// Protect against direct access
defined('_JEXEC') or die;

/** @var array $displayData */
$invoiceId = $displayData['invoiceId'] ?? 0;
?>
<h1>Pay By Check Payment Instructions</h1>
<p>Invoice ID: <?php echo (int) $invoiceId; ?></p>
<p>
  Please send payment via Zelle to <strong>payments@example.com</strong>.
</p>
<p>
  After sending the payment, return to the site to confirm.
</p>
