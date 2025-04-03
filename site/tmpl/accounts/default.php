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
<h1>Accounts</h1>
<table class="table accountsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Account</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fee Amount</th>
            <th>Account Method</th>
            <th>Transaction Id</th>
            <th>Invoices</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->accounts)) : ?>
            <tr>
                <td colspan="7">No accounts found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->accounts as $account) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=account&id=' . $account->id); ?>"><?php echo $account->id; ?></a></td>
                <td><?php echo $account->account_name; ?></td>
                <td>$<?php echo number_format($account->amount, 2); ?></td>
                <td><?php echo $account->status; ?></td>
                <td>$<?php echo number_format($account->fee_amount, 2); ?></td>
                <td><?php echo $account->account_method; ?></td>
                <td><?php echo $account->transaction_id; ?></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $account->invoice_ids); ?>" ><?php echo $account->invoice_ids; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="card mt-4">
  <div class="card-header">
    Account Status Legend
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Pending</strong>: Account is awaiting confirmation.</li>
      <li><strong>Completed</strong>: Account was successful.</li>
      <li><strong>Failed</strong>: Account failed to process.</li>
      <li><strong>Cancelled</strong>: Account was cancelled.</li>
      <li><strong>Refunded</strong>: Account was returned to the payer.</li>
    </ul>
  </div>
</div>