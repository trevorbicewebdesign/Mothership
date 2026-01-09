<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$account = $this->item;
?>
<h1><?php echo $account->name; ?></h1>
<hr/>
<h4>Proposals</h4>
<table class="table" id="proposalsTable">
    <thead>
        <tr>
            <th>PDF</th>
            <th>#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Expires</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($account->proposals)) : ?>
            <tr>
                <td colspan="8">No proposals found.</td>
            </tr>
        <?php endif; ?>
        <?php 
        if( isset($account->proposals) && is_array($account->proposals)):
        foreach ($account->proposals as $proposal ) : ?>
            <tr>
                <td>    
                    <a href="<?php echo Route::_('index.php?option=com_mothership&task=proposal.downloadPdf&id=' . $proposal->id); ?>" target="_blank">PDF</a>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposal->id); ?>"><?php echo $proposal->number; ?></a></td>                
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=proposal&id=' . $proposal->id); ?>"><?php echo $proposal->name; ?></a></td>
                <td><?php 
                switch($proposal->type) {
                    case 'hourly':
                        $proposal->type = "Hourly";
                        break;
                }
                echo $proposal->type; 
                ?></td>
                <td>$<?php echo number_format($proposal->total_low, 2); ?> - $<?php echo number_format($proposal->total, 2); ?></td>
                <td><?php 
                switch ($proposal->status) {
                    case '2':
                        echo "Pending";
                        break;
                    case '3':
                        echo "Approved";
                        break;
                    case '4':
                        echo "Declined";
                        break;
                    case '5':
                        echo "Cancelled";
                        break;
                    case '6':
                        echo "Expired";
                        break;
                    default:
                        echo "Unknown";
                        break;
                }
                
                ?></td>
                <td><?php echo $proposal->expires; ?></td>
                <td>
                    
                    <ul style="margin-bottom:0px;">
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=proposal.edit&id=' . $proposal->id); ?>">View</a></li>
                        <?php if($proposal->status === 2): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=proposal.approve&id={$proposal->id}"); ?>">Approve</a></li>
                        <?php endif; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach;
        endif;
        ?>
    </tbody>
</table>
<hr/>
<h4>Invoices</h4>
<table class="table" id="invoicesTable">
    <thead>
        <tr>
            <th>PDF</th>
            <th>#</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($account->invoices)) : ?>
            <tr>
                <td colspan="7">No invoices found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($account->invoices as $invoice) : ?>
            <tr>
                <td>    
                    <a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . $invoice->id); ?>" target="_blank">PDF</a>
                </td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $invoice->id); ?>"><?php echo $invoice->number; ?></a></td>                
                <td>$<?php echo number_format($invoice->total, 2); ?></td>
                <td><?php echo $invoice->status; ?></td>
                <td>
                    <?php echo $invoice->payment_status; ?><br/>
                    <?php 
                    if( is_array($invoice->payment_ids)):
                        $payment_ids = array_filter(explode(",", $invoice->payment_ids)); ?>
                        <?php if (count($payment_ids) > 0): ?>
                        <ul style="margin-bottom:0px;">
                            <?php foreach ($payment_ids as $paymentId): ?>
                                <li style="list-style: none;"><small><a href="index.php?option=com_mothership&view=payment&id=<?php echo $paymentId; ?>&return=<?php echo base64_encode(Route::_('index.php?option=com_mothership&view=invoices')); ?>"><?php echo "Payment #" . str_pad($paymentId, 2, "0", STR_PAD_LEFT); ?></a></small></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($invoice->status === 'Opened' || $invoice->status === 'Late'): ?>
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
                    <ul style="margin-bottom:0px;">
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.edit&id=' . $invoice->id); ?>">View</a></li>
                        <?php if($invoice->status === 'Opened' || $invoice->status === 'Late'): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=invoice.payment&id={$invoice->id}"); ?>">Pay</a></li>
                        <?php endif; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr/>
<h4>Payments</h4>
<table class="table paymentsTable" id="paymentsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fee Amount</th>
            <th>Payment Method</th>
            <th>Transaction Id</th>
            <th>Invoices</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($account->payments)) : ?>
            <tr>
                <td colspan="7">No payments found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($account->payments as $payment) : 
            ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=payment&id=' . $payment->id); ?>"><?php echo $payment->id; ?></a></td>
                <td>$<?php echo number_format($payment->amount, 2); ?></td>
                <td><?php echo $payment->status; ?></td>
                <td>$<?php echo number_format($payment->fee_amount, 2); ?></td>
                <td><?php echo $payment->payment_method; ?></td>
                <td><?php echo $payment->transaction_id; ?></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $payment->invoice_ids); ?>" ><?php echo $payment->invoice_ids; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr/>
<h4>Projects</h4>
<table class="table projectsTable " id="projectsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($account->projects)) : ?>
            <tr>
                <td colspan="8">No projects found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($account->projects as $project) : ?>
            <tr>
                <td><a href="<?php echo Route::_("index.php?option=com_mothership&view=project&id={$project->id}"); ?>"><?php echo $project->id; ?></td>
                <td><a href="<?php echo Route::_("index.php?option=com_mothership&view=project&id={$project->id}"); ?>"><?php echo $project->name; ?></a></td>
                <td><?php echo $project->type; ?></td>
                <td><?php echo $project->status; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr/>
<h4>Domains</h4>
<table class="table domainsTable " id="domainsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Domains</th>
            <th>Registrar</th>
            <th>Reseller</th>
            <th>DNS</th>
            <th>Expiration</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($account->domains)) : ?>
            <tr>
                <td colspan="8">No domains found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($account->domains as $domains) : ?>
            <tr>
                <td><?php echo $domains->id; ?></td>
                <td><a href="<?php echo Route::_("index.php?option=com_mothership&view=domain&id={$domains->id}"); ?>"><?php echo $domains->name; ?></a></td>
                <td><?php echo $domains->registrar; ?></td>
                <td><?php echo $domains->reseller; ?></td>
                <td><?php echo $domains->dns_provider; ?></td>
                <td><?php echo $domains->expiration_date; ?></td>
                <td><?php echo $domains->status; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>