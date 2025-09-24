<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$payment = $this->item;
$statusColor = match ((int) $payment->status) {
    1 => 'warning',   // Pending
    2 => 'success',   // Completed
    3 => 'danger',    // Cancelled
    4 => 'secondary', // Refunded
    5 => 'info',      // Other
    default => 'dark',
};
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Cancel Payment</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <strong>Warning:</strong> Canceling this payment will <u>not</u> void the invoice itself. This only cancels the payment record and allows you to initiate a new payment if needed.
            </div>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment->id); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment->payment_method ?? 'Unknown'); ?></p>
            <p><strong>Amount:</strong> <span class="text-success fw-bold">$<?php echo number_format($payment->amount, 2); ?></span></p>

                <strong>Status:</strong>
                <span class="badge bg-<?php echo $statusColor; ?>">
                    <?php echo $payment->status_text ?? $payment->status; ?>
                </span>
            </p>
            <p>
                <strong>Payment Date:</strong>
                <?php echo $payment->payment_date ? htmlspecialchars($payment->payment_date) : '—'; ?>
            </p>
            <?php if ((int)$payment->status === 1): // Only allow cancel if pending ?>
                <form action="<?php echo Route::_('index.php?option=com_mothership&task=payment.cancel'); ?>" method="post">
                    <input type="hidden" name="id" value="<?php echo (int) $payment->id; ?>">
                    <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to cancel this payment? This action cannot be undone.');">
                            Cancel Payment
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    This payment cannot be cancelled. Only pending payments can be cancelled.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
