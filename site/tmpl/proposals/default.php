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
            <th>Name</th>
            <th>Client</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment Status</th>
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
                <td><?php echo $proposal->name; ?></td>
                <td><?php echo $proposal->client_name; ?></td>
                <td><?php echo $proposal->account_name; ?></td>
                <td>$<?php echo number_format($proposal->total, 2); ?></td>
                <td><?php echo $proposal->status; ?></td>
                <td> </td>
                <td>
                    <ul>
                        <li><a href="<?php echo Route::_('index.php?option=com_mothership&task=proposal.edit&id=' . $proposal->id); ?>">View</a></li>
                        <?php if($proposal->status === 'Pending' ): ?>
                        <li><a href="<?php echo Route::_("index.php?option=com_mothership&task=proposal.approve&id={$proposal->id}"); ?>">Approve</a></li>
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
                    <li><strong>Pending</strong>: Proposal has been created and is awaiting review or approval.</li>
                    <li><strong>Approved</strong>: Proposal has been accepted and is ready for payment or further processing.</li>
                    <li><strong>Declined</strong>: Proposal has been reviewed and was not accepted.</li>
                    <li><strong>Canceled</strong>: Proposal was canceled and is no longer active.</li>
                    <li><strong>Expired</strong>: Proposal has passed its valid until date and is no longer valid.</li>
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

