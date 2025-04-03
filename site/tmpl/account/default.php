<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$account = $this->item;
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Account #<?php echo $account->id; ?></h4>
            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($account->account_method); ?></span>
        </div>
        <div class="card-body">
            <p>
                <strong>Amount:</strong>
                <span class="text-success fw-bold">$<?php echo number_format($account->amount, 2); ?></span>
            </p>

            <p>
                <strong>Status:</strong>
                <?php
                    $statusColor = match ((int) $account->status) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        4 => 'secondary',
                        5 => 'info',
                        default => 'dark',
                    };
                ?>
                <span class="badge bg-<?php echo $statusColor; ?>">
                    <?php echo $account->status_text ?? $account->status; ?>
                </span>
            </p>

            <p>
                <strong>Account Date:</strong>
                <?php echo htmlspecialchars($account->account_date); ?>
            </p>

            <?php if (!empty($account->invoice_ids)) : ?>
                <hr>
                <p><strong>Invoices Paid With This Account:</strong></p>
                <ul class="list-group list-group-flush">
                    <?php
                    $ids = explode(',', $account->invoice_ids);
                    $numbers = explode(',', $account->invoice_numbers);
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
