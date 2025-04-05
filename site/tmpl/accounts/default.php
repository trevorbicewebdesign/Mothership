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
<table class="table accountsTable" id="accountsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Account</th>    
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
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=account&id=' . $account->id); ?>"><?php echo $account->account_name; ?></a></td>        
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>