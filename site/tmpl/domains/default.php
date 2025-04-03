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
<h1>Domains</h1>
<table class="table domainssTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Domains</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fee Amount</th>
            <th>Domains Method</th>
            <th>Transaction Id</th>
            <th>Invoices</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->domainss)) : ?>
            <tr>
                <td colspan="7">No domainss found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->domainss as $domains) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=domains&id=' . $domains->id); ?>"><?php echo $domains->id; ?></a></td>
                <td><?php echo $domains->domains_name; ?></td>
                <td>$<?php echo number_format($domains->amount, 2); ?></td>
                <td><?php echo $domains->status; ?></td>
                <td>$<?php echo number_format($domains->fee_amount, 2); ?></td>
                <td><?php echo $domains->domains_method; ?></td>
                <td><?php echo $domains->transaction_id; ?></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=invoice&id=' . $domains->invoice_ids); ?>" ><?php echo $domains->invoice_ids; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="card mt-4">
  <div class="card-header">
    Domains Status Legend
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Pending</strong>: Domains is awaiting confirmation.</li>
      <li><strong>Completed</strong>: Domains was successful.</li>
      <li><strong>Failed</strong>: Domains failed to process.</li>
      <li><strong>Cancelled</strong>: Domains was cancelled.</li>
      <li><strong>Refunded</strong>: Domains was returned to the payer.</li>
    </ul>
  </div>
</div>