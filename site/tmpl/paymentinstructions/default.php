<?php
/**
 * @var Mothership\Component\Mothership\Site\View\Paymentinstructions\HtmlView $this
 */

defined('_JEXEC') or die;
?>
<h2>Payment Instructions</h2>

<?php if (empty($this->instructions)): ?>
    <p>No instructions found for this payment method.</p>
<?php else: ?>
    <?php echo $this->instructions; ?>
<?php endif; ?>
