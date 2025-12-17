<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

?>
<style>
    .mt-4 {
        margin-top: 1.5rem;
    }
</style>
<h1>Proposals</h1>
<table class="table" id="proposalsTable">
    <thead>
        <tr>
            <th>PDF</th>
            <th>#</th>
            <th>Client</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->proposals)) : ?>
            <tr>
                <td colspan="9">No proposals found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->proposals as $proposal) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&task=proposal.downloadPdf&id=' . $proposal->id); ?>" target="_blank">PDF</a></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposal->id); ?>"><?php echo $proposal->number; ?></a></td>
                <td><?php echo $proposal->client_name; ?></td>
                <td><?php echo $proposal->account_name; ?></td>
                <td>$<?php echo number_format($proposal->total, 2); ?></td>
                <td><?php echo $proposal->status; ?></td>
                <td>
                    <?php echo $proposal->payment_status; ?><br/>
                    <?php $payment_ids = array_filter(explode(",", $proposal->payment_ids ?? '')); ?>
                    <?php if (count($payment_ids) > 0): ?>
                    <ul style="margin-bottom:0px;" class="payment-list">
                        <?php foreach ($payment_ids as $paymentId): ?>
                            <li style="list-style: none;"><small><a href="index.php?option=com_mothership&view=payment&id=<?php echo $paymentId; ?>&return=<?php echo base64_encode(Route::_('index.php?option=com_mothership&view=proposals')); ?>" class="payment-link"><?php echo "Payment #" . str_pad($paymentId, 2, "0", STR_PAD_LEFT); ?></a></small></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($proposal->status === 'Opened'): ?>
                    <?php
                    $dueDate = new DateTime($proposal->due_date, new DateTimeZone('UTC'));
                    $dueDate->setTime(23, 59, 59);
                    
                    $currentDate = new DateTime('now', new DateTimeZone('UTC'));
                    $interval = $currentDate->diff($dueDate);
                    echo "Due in {$interval->days} days";
                    ?>
                    <?php endif; ?>
                </td>
                
                <td>
                    <ul>
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=proposal.edit&id=' . $proposal->id); ?>">View</a></li>
                        <?php if($proposal->status === 'Opened' && $proposal->payment_status != 'Pending Confirmation'): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=proposal.payment&id={$proposal->id}"); ?>">Pay</a></li>
                        <?php elseif($proposal->payment_status ==='Pending Confirmation'):?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=payment.cancel&id={$paymentId}"); ?>">Cancel Pending Payment</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Proposal Status Legend
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Opened</strong>: Proposal is awaiting payment.</li>
                    <li><strong>Cancelled</strong>: Proposal has been voided and is no longer valid.</li>
                    <li><strong>Closed</strong>: Proposal has been paid and is no longer active.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Payment Status Legend
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Unpaid</strong>: Payment has not been made yet.</li>
                    <li><strong>Paid</strong>: Payment has been completed in full.</li>
                    <li><strong>Partially Paid</strong>: A partial payment has been made, but the full amount is still outstanding.</li>
                    <li><strong>Pending Confirmation</strong>: Payment has been initiated but is awaiting confirmation.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

