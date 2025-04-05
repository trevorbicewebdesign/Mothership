<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$payment = $this->item;
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Payment #<?php echo $payment->id; ?></h4>
            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($payment->payment_method); ?></span>
        </div>
        <div class="card-body">
            <p>
                <strong>Amount:</strong>
                <span class="text-success fw-bold">$<?php echo number_format($payment->amount, 2); ?></span>
            </p>

            <p>
                <strong>Status:</strong>
                <?php
                    $statusColor = match ((int) $payment->status) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        4 => 'secondary',
                        5 => 'info',
                        default => 'dark',
                    };
                ?>
                <span class="badge bg-<?php echo $statusColor; ?>">
                    <?php echo $payment->status_text ?? $payment->status; ?>
                </span>
            </p>

            <p>
                <strong>Payment Date:</strong>
                <?php echo htmlspecialchars($payment->payment_date); ?>
            </p>

            <?php if (!empty($payment->invoice_ids)) : ?>
                <hr>
                <p><strong>Invoices Paid With This Payment:</strong></p>
                <ul class="list-group list-group-flush">
                    <?php
                    $ids = explode(',', $payment->invoice_ids);
                    $numbers = explode(',', $payment->invoice_numbers);
                    foreach ($ids as $i => $invoiceId) :
                        $number = $numbers[$i] ?? $invoiceId;
                        $url = Route::_('index.php?option=com_mothership&view=invoice&id=' . (int) $invoiceId);
                    ?>
                        <li class="list-group-item">
                            <a href="<?php echo $url; ?>">
                                Invoice #<?php echo htmlspecialchars($number); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
