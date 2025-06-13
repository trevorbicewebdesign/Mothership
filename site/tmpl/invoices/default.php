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
<h1>Invoices</h1>
<table class="table" id="invoicesTable">
    <thead>
        <tr>
            <th>PDF</th>
            <th>#</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->invoices)) : ?>
            <tr>
                <td colspan="8">No invoices found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->invoices as $invoice) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . $invoice->id); ?>" target="_blank">PDF</a></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoice->id); ?>"><?php echo $invoice->number; ?></a></td>
                <td><?php echo $invoice->account_name; ?></td>
                <td>$<?php echo number_format($invoice->total, 2); ?></td>
                <td><?php echo $invoice->status; ?></td>
                <td>
                    <?php echo $invoice->payment_status; ?><br/>
                    <?php $payment_ids = array_filter(explode(",", $invoice->payment_ids ?? '')); ?>
                    <?php if (count($payment_ids) > 0): ?>
                    <ul style="margin-bottom:0px;">
                        <?php foreach ($payment_ids as $paymentId): ?>
                            <li style="list-style: none;"><small><a href="index.php?option=com_mothership&view=payment&id=<?php echo $paymentId; ?>&return=<?php echo base64_encode(Route::_('index.php?option=com_mothership&view=invoices')); ?>"><?php echo "Payment #" . str_pad($paymentId, 2, "0", STR_PAD_LEFT); ?></a></small></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($invoice->status === 'Opened'): ?>
                    <?php
                    $dueDate = new DateTime($invoice->due_date, new DateTimeZone('UTC'));
                    $dueDate->setTime(23, 59, 59);
                    
                    $currentDate = new DateTime('now', new DateTimeZone('UTC'));
                    $interval = $currentDate->diff($dueDate);
                    echo "Due in {$interval->days} days";
                    ?>
                    <?php endif; ?>
                </td>
                
                <td>
                    <ul>
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.edit&id=' . $invoice->id); ?>">View</a></li>
                        <?php if($invoice->status === 'Opened' && $invoice->payment_status != 'Pending Confirmation'): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=invoice.payment&id={$invoice->id}"); ?>">Pay</a></li>
                        <?php elseif($invoice->status ==='Pending Confirmation'):?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=invoice.payment&id={$invoice->id}"); ?>">Cancel Pending Payment</a></li>
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
                Invoice Status Legend
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Opened</strong>: Invoice is awaiting payment.</li>
                    <li><strong>Cancelled</strong>: Invoice has been voided and is no longer valid.</li>
                    <li><strong>Closed</strong>: Invoice has been paid and is no longer active.</li>
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

