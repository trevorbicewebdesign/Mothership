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
<h1>Clients</h1>
<table class="table clientsTable" id="clientsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Client</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->clients)) : ?>
            <tr>
                <td colspan="2">No clients found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->clients as $client) : ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=account&id=' . $client->id); ?>"><?php echo $client->id; ?></a></td>
                <td><a href="<?php echo Route::_('index.php?option=com_mothership&view=client&id=' . $client->client_id); ?>"><?php echo $client->client_name; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>