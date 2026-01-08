<?php
defined('_JEXEC') or die;

// Ensure Joomla autoloader is available
if (!class_exists('JLoader')) {
    require_once JPATH_LIBRARIES . '/loader.php';
}

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$proposal = (object) $this->item;

$id = (int) ($proposal->id ?? 0);
$number = (string) ($proposal->number ?? '');
$name = (string) ($proposal->name ?? '');
$summary = (string) ($proposal->summary ?? '');

$total = (float) ($proposal->total ?? 0.0);
$totalLow = (float) ($proposal->total_low ?? 0.0);
$isRange = $totalLow > 0 && $totalLow !== $total;

$currency = (string) ($proposal->currency ?? 'USD');

$hourly = '$' . number_format((float) ($proposal->rate ?? 0.0), 2);
?>


<h1 class="mb-2">Approve Proposal #<?php echo htmlspecialchars($number, ENT_QUOTES, 'UTF-8'); ?></h1>
<hr>

<h3><?php echo $name; ?></h3>

<?php if ($summary !== ''): ?>
    <?php echo $summary; ?>
<?php endif; ?>

<hr>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="fw-semibold">Estimate of Costs</div>
    <div class="fs-5 fw-semibold">
        <?php if ($isRange): ?>
            <?php echo number_format($totalLow, 2); ?> – <?php echo number_format($total, 2); ?>
        <?php else: ?>
            <?php echo number_format($total, 2); ?>
        <?php endif; ?>
        <span class="text-muted small"><?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
</div>

<div class="alert alert-secondary mb-0">

    <h3>Terms of Acceptance</h3>
    <strong>Time + Costs</strong>

    <p>
        Work is billed based on time spent at the hourly rate of
        <strong><?php echo $hourly; ?></strong>, plus any applicable project-related costs.
    </p>

    <ul class="mb-0 mt-2">
        <li>Approval authorizes only the work required to fulfill this proposal.</li>
        <li>Time is billed based on actual hours worked to complete the work. This could be longer than the estimated
            time. </li>
    </ul>

</div>

<hr />

<div class="d-flex gap-2 justify-content-end">
    <form action="<?php echo Route::_('index.php?option=com_mothership&task=proposal.approveConfirm'); ?>"
        method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
        <button type="submit" class="btn btn-primary btn-lg">
            Approve Proposal
        </button>
    </form>

    <form action="<?php echo Route::_('index.php?option=com_mothership&task=proposal.denyConfirm'); ?>" method="post"
        onsubmit="return confirm('Deny this proposal?');">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
        <button type="submit" class="btn btn-outline-danger btn-lg">
            Deny
        </button>
    </form>
</div>