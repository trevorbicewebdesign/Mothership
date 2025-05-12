<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var object $this->item */
/** @var array $this->paymentOptions */

$invoice = (object) $this->item;
$total = (float) $invoice->total;

$feesJson = json_encode(array_column($this->paymentOptions, 'fee_amount'));
$instructionsJson = json_encode(array_column($this->paymentOptions, 'instructions_html'));
echo HTMLHelper::_('jquery.framework'); // Ensure jQuery is loaded
?>

<style>
.payment-instructions {
    margin-top: 1rem;
    padding: 0.75rem;
    border-left: 4px solid #007BFF;
    background-color: #f8f9fa;
    display: none;
}
</style>

<h1>Pay Invoice #<?php echo htmlspecialchars($invoice->number); ?></h1>

<?php if (!empty($this->paymentOptions)) : ?>
    <form action="<?php echo Route::_('/index.php?option=com_mothership&task=invoice.processPayment&id=' . (int) $invoice->id); ?>" method="post">
        <div style="text-align:right;width:100%;display:block;">
            <span style="font-weight:bold">Total Due:</span> $<?php echo number_format($total, 2); ?>
        </div>

        <hr/>
        <div style="text-align:right;width:100%;display:block;">
            <span style="font-weight:bold">Select Payment Method:</span>
        </div>

        <?php foreach ($this->paymentOptions as $index => $method) : ?>
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
                    <?php echo $method['display_fee']; ?>: $<?php echo $method['fee_amount']; ?>
                </span>
            </div>
        <?php endforeach; ?>

        <hr />

        <div style="text-align:right;width:100%;display:block;">
            <span style="font-weight:bold">Total: $<span id="payTotal"><?php echo number_format($total, 2); ?></span></span>
        </div>

        <div id="selected-instructions" class="payment-instructions" aria-live="polite"></div>

        <button type="submit" class="btn btn-primary" style="float:right;">Pay Now</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
<?php else : ?>
    <div class="alert alert-warning">
        No payment methods are available at this time.
    </div>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const fees = <?php echo $feesJson; ?>;
    const instructions = <?php echo $instructionsJson; ?>;
    const total = <?php echo json_encode($total); ?>;

    $('input[name="payment_method"]').on('change', function () {
        const selectedIndex = $('input[name="payment_method"]').index(this);
        const selectedKey = $(this).val();

        const feeAmount = parseFloat(fees[selectedIndex] || 0);
        const totalWithFee = total + feeAmount;
        $('#payTotal').text(totalWithFee.toFixed(2));

        const instructionHtml = instructions[selectedIndex] || '';
        const $instructions = $('#selected-instructions');

        // Slide up, replace content, and slide down
        $instructions.stop(true, true).slideUp(150, function () {
            $(this).html(instructionHtml).slideDown(200);
        });
    });

    // Auto-select first option
    const $first = $('input[name="payment_method"]').first();
    if ($first.length) {
        $first.prop('checked', true).trigger('change');
    }
});
</script>
