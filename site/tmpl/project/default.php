<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$project = $this->item;
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Project #<?php echo $project->id; ?></h4>
            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($project->project_method); ?></span>
        </div>
        <div class="card-body">
            <p>
                <strong>Amount:</strong>
                <span class="text-success fw-bold">$<?php echo number_format($project->amount, 2); ?></span>
            </p>

            <p>
                <strong>Status:</strong>
                <?php
                    $statusColor = match ((int) $project->status) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        4 => 'secondary',
                        5 => 'info',
                        default => 'dark',
                    };
                ?>
                <span class="badge bg-<?php echo $statusColor; ?>">
                    <?php echo $project->status_text ?? $project->status; ?>
                </span>
            </p>

            <p>
                <strong>Project Date:</strong>
                <?php echo htmlspecialchars($project->project_date); ?>
            </p>

            <?php if (!empty($project->invoice_ids)) : ?>
                <hr>
                <p><strong>Invoices Paid With This Project:</strong></p>
                <ul class="list-group list-group-flush">
                    <?php
                    $ids = explode(',', $project->invoice_ids);
                    $numbers = explode(',', $project->invoice_numbers);
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
