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
            <th>Domains</th>
            <th>Account</th>
            <th>Registrar</th>
            <th>DNS</th>
            <th>Created</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->domains)) : ?>
            <tr>
                <td colspan="7">No domains found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->domains as $domains) : ?>
            <tr>
                <td><?php echo $domains->id; ?></td>
                <td><?php echo $domains->name; ?></td>
                <td><?php echo $domains->account_name; ?></td>
                <td><?php echo $domains->registrar; ?></td>
                <td><?php echo $domains->dns_provider; ?></td>
                <td><?php echo $domains->created; ?></td>
                <td><?php echo $domains->status; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>