<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
?>

<div class="container mt-5">
    <h1 class="text-success">Thank You</h1>
    <p>Your payment was successfully received.</p>
    <?php if (!empty($this->invoiceId)) : ?>
        <p>Invoice #<?php echo (int) $this->invoiceId; ?></p>
        <p>Payment Method: <?php echo $this->payment_method; ?></p>
        <p>Payment Status: pending</p>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?php echo Route::_('index.php?option=com_mothership&view=payments'); ?>" class="btn btn-primary">Return to Payments</a>
    </div>
</div>
