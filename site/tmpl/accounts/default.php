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
        
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>