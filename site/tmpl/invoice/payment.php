<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var object $this->item */
/** @var array $this->paymentOptions */

$invoice = $this->item;
$total = (float) $invoice->total;
?>

<h1>Pay Invoice #<?php echo htmlspecialchars($invoice->number); ?></h1>

<p><strong>Total Due:</strong> $<?php echo number_format($total, 2); ?></p>

<?php if (!empty($this->paymentOptions)) : ?>
    <form action="<?php echo Route::_('index.php?option=com_mothership&task=invoice.processPayment&id=' . (int) $invoice->id); ?>" method="post">
        <h2>Select Payment Method:</h2>

        <?php foreach ($this->paymentOptions as $index => $method) : 
            $feePercent = (float) $method['fee_percent'];
            $feeFixed = (float) $method['fee_fixed'];
            $feeAmount = ($total * ($feePercent / 100)) + $feeFixed;
            $totalWithFee = $total + $feeAmount;
        ?>
            <div class="payment-method" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ddd; border-radius: 4px;">
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
                <div style="margin-left: 1.5rem; font-size: 0.9rem; color: #555;">
                    <?php if ($feePercent || $feeFixed) : ?>
                        <p>Fee: 
                            <?php
                                $parts = [];
                                if ($feePercent) {
                                    $parts[] = $feePercent . '%';
                                }
                                if ($feeFixed) {
                                    $parts[] = '$' . number_format($feeFixed, 2);
                                }
                                echo implode(' + ', $parts);
                            ?>
                        </p>
                        <p>Total with fees: <strong>$<?php echo number_format($totalWithFee, 2); ?></strong></p>
                    <?php else : ?>
                        <p>No additional fees.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Pay Now</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
<?php else : ?>
    <div class="alert alert-warning">
        No payment methods are available at this time.
    </div>
<?php endif; ?>
