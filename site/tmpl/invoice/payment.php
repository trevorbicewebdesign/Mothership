<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var object $this->item */
/** @var array $this->paymentOptions */

$invoice = (object) $this->item;
$total = (float) $invoice->total;
?>

<h1>Pay Invoice #<?php echo htmlspecialchars($invoice->number); ?></h1>

<?php if (!empty($this->paymentOptions)) : ?>
    <form action="<?php echo Route::_('/index.php?option=com_mothership&task=invoice.processPayment&id=' . (int) $invoice->id); ?>" method="post">
        <div style="text-align:right;width:100%;display:block;"><span style="font-weight:bold">Total Due:</span> $<?php echo number_format($total, 2); ?></div>
        <hr/>
        <div style="text-align:right;width:100%;display:block;"><span style="font-weight:bold">Select Payment Method:</span></div>

        <?php foreach ($this->paymentOptions as $index => $method) : 
            // Retrieve fee configuration from the payment method
                // Calculate fee amount and the total including fee
                $feeAmount = $method['fee_amount'];
                $totalWithFee = $total + $feeAmount;
                // Use the plugin's function feeDisplay
                $feeDisplay = $method['display_fee'];
            
        ?>
            <div class="payment-method" style="text-align:right;">
                <label for="payment_method_<?php echo $index; ?>">
                    <input
                        type="radio"
                        name="payment_method"
                        id="payment_method_<?php echo $index; ?>"
                        value="<?php echo htmlspecialchars($method['element']); ?>"
                        required
                    >
                    <?php echo htmlspecialchars($method['name']); ?>
                </label>
                <span style="font-size: 0.9rem; color: #555;">
                    <?php echo $feeDisplay; ?>: $<?php echo $feeAmount; ?>
                </span>
            </div>
        <?php endforeach; ?>
        <hr />
        <div style="text-align:right;width:100%;display:block;"><span style="font-weight:bold">Total: <div id="payTotal"><?php echo $totalWithFee; ?></div></span>
        <button type="submit" class="btn btn-primary" style="float:right;">Pay Now</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
<?php else : ?>
    <div class="alert alert-warning">
        No payment methods are available at this time.
    </div>
<?php endif; ?>

<script>
// Update the #payTotal element with the total amount including the fee
document.addEventListener('DOMContentLoaded', function() {
    var paymentMethods = document.querySelectorAll('.payment-method input[name="payment_method"]');
    var total = <?php echo $total; ?>;
    var fees = <?php echo json_encode(array_column($this->paymentOptions, 'fee_amount')); ?>;

    paymentMethods.forEach(function(method, index) {
        method.addEventListener('change', function() {
            var feeAmount = parseFloat(fees[index]);
            var totalWithFee = total + feeAmount;
            document.getElementById('payTotal').textContent = totalWithFee.toFixed(2);
        });
    });
});
</script>
