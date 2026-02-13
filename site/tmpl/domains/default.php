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
<table class="table domainsTable " id="domainsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Domain</th>
            <th>Account</th>
            <th>Client</th>
            <th>Registrar</th>
            <th>Reseller</th>
            <th>DNS</th>
            <th>Created</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($this->domains)): ?>
            <tr>
                <td colspan="9">No domains found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->domains as $domains): ?>
            <tr>
                <td><?php echo $domains->id; ?></td>
                <td><a  href="<?php echo Route::_('index.php?option=com_mothership&view=domain&id=' . $domains->id); ?>"><?php echo $domains->name; ?></a></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=account&id=' . $domains->account_id); ?>"><?php echo $domains->account_name; ?></a></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=client&id=' . $domains->client_id); ?>"><?php echo $domains->client_name; ?></a></td>
                <td><?php echo $domains->registrar; ?></td>
                <td><?php echo $domains->reseller; ?></td>
                <td><?php echo $domains->dns_provider; ?></td>
                <td><?php echo $domains->created; ?></td>
                <td><a  href="<?php echo Route::_('index.php?option=com_mothership&view=domain&id=' . $domains->id); ?>"><?php echo $domains->status; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>