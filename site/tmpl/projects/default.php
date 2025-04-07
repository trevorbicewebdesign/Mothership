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
<h1>Projects</h1>
<table class="table projectsTable " id="projectsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Account</th>
            <th>Type</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($this->projects)) : ?>
            <tr>
                <td colspan="6">No projects found.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($this->projects as $projects) : ?>
            <tr>
                <td><?php echo $projects->id; ?></td>
                <td><a href="<?php echo Route::_("index.php?option=com_mothership&view=project&id={$projects->id}"); ?>"><?php echo $projects->name; ?></a></td>
                <td><a href="<?php echo Route::_("index.php?option=com_mothership&view=account&id={$projects->account_id}"); ?>"><?php echo $projects->account_name; ?></a></td>
                <td><?php echo $projects->type; ?></td>
                <td><?php echo $projects->status; ?></td>
                <td><?php echo $projects->created; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>