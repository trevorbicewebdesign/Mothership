<?php
/**
 * One person's portal access, grouped by role. Ownership is read-only here (it's set on the
 * client); any other access can be re-invited or removed, and new access given.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Portaluser\HtmlView $this */

$person    = $this->person;
$action    = Route::_('index.php?option=com_mothership');
$token     = HTMLHelper::_('form.token');
$e         = fn ($v) => $this->escape((string) $v);
$clientUrl = fn (int $id) => Route::_('index.php?option=com_mothership&task=client.edit&id=' . $id);

$roleBadge = [
    ClientAccess::ROLE_OWNER => 'bg-primary',
    ClientAccess::ROLE_FULL  => 'bg-primary-subtle text-primary-emphasis',
];
?>
<style>
.ms-person-head { display:flex; flex-wrap:wrap; gap:8px 24px; align-items:baseline; margin-bottom:1.5rem; }
.ms-person-head h2 { margin:0; }
.ms-muted { opacity:.7; }
.ms-role-section { margin-bottom:1.75rem; }
.ms-role-section > header { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:.5rem; }
.ms-role-section > header h3 { margin:0; font-size:1.1rem; }
.ms-owned { columns: 3 16rem; column-gap:2rem; margin:0; padding-left:1.1rem; }
.ms-owned li { break-inside:avoid; margin-bottom:2px; }
.ms-role-section details summary { cursor:pointer; }
.ms-access-table td, .ms-access-table th { vertical-align:middle; }
</style>

<div class="ms-person-head">
    <h2><?php echo $e($person->name); ?></h2>
    <span><?php echo $e($person->email); ?></span>
    <span class="ms-muted"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_HEADING_LAST_VISIT'); ?>:
        <?php echo $person->lastvisitDate ? HTMLHelper::_('date', $person->lastvisitDate, Text::_('DATE_FORMAT_LC4')) : Text::_('COM_MOTHERSHIP_PORTALUSERS_NEVER'); ?></span>
    <?php if ((int) $person->block === 1) : ?>
        <span class="badge bg-danger"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_BLOCKED'); ?></span>
    <?php endif; ?>
</div>

<?php if (!$this->byRole) : ?>
    <div class="alert alert-info"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_NO_ACCESS'); ?></div>
<?php endif; ?>

<?php foreach ($this->byRole as $role => $entries) : ?>
    <section class="ms-role-section card card-body" data-role="<?php echo $e($role); ?>">
        <header>
            <span class="badge <?php echo $roleBadge[$role] ?? 'bg-secondary'; ?>"><?php echo $e(ClientAccess::roleLabel($role)); ?></span>
            <h3><?php echo Text::plural('COM_MOTHERSHIP_PORTALUSER_N_CLIENTS', \count($entries)); ?></h3>
            <span class="ms-muted small"><?php echo $e(ClientAccess::roleDescription($role)); ?></span>
        </header>

        <?php if ($role === ClientAccess::ROLE_OWNER) : ?>
            <?php ob_start(); ?>
            <ul class="ms-owned">
                <?php foreach ($entries as $entry) : ?>
                    <li><a href="<?php echo $clientUrl($entry->client_id); ?>"><?php echo $e($entry->client_name); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php $ownedList = ob_get_clean(); ?>
            <?php // A long list of owned clients folds away so the other roles stay in view. ?>
            <?php if (\count($entries) > 8) : ?>
                <details>
                    <summary><?php echo Text::sprintf('COM_MOTHERSHIP_PORTALUSER_SHOW_OWNED', \count($entries)); ?></summary>
                    <div class="mt-2"><?php echo $ownedList; ?></div>
                </details>
            <?php else : ?>
                <?php echo $ownedList; ?>
            <?php endif; ?>
            <p class="small ms-muted mb-0 mt-2"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_OWNER_NOTE'); ?></p>
        <?php else : ?>
            <table class="table ms-access-table mb-0">
                <thead>
                    <tr>
                        <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_FILTER_CLIENT_LABEL'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_STATUS'); ?></th>
                        <th scope="col" class="text-end"><span class="visually-hidden"><?php echo Text::_('JACTION'); ?></span></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry) : ?>
                    <tr>
                        <td><a href="<?php echo $clientUrl($entry->client_id); ?>"><?php echo $e($entry->client_name); ?></a></td>
                        <td>
                            <?php if ($entry->invite_expired) : ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_EXPIRED'); ?></span>
                            <?php elseif ($entry->pending) : ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_PENDING'); ?></span>
                            <?php else : ?>
                                <span class="badge bg-success-subtle text-success-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_ACTIVE'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($this->canEdit) : ?>
                                <?php if ($entry->pending) : ?>
                                    <form method="post" action="<?php echo $action; ?>" class="d-inline">
                                        <input type="hidden" name="task" value="portaluser.resend">
                                        <input type="hidden" name="id" value="<?php echo (int) $person->id; ?>">
                                        <input type="hidden" name="access_id" value="<?php echo (int) $entry->access_id; ?>">
                                        <?php echo $token; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_RESEND'); ?></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?php echo $action; ?>" class="d-inline" onsubmit="return confirm(<?php echo $e(json_encode(Text::sprintf('COM_MOTHERSHIP_PORTALUSER_REMOVE_CONFIRM', $person->name, $entry->client_name))); ?>);">
                                    <input type="hidden" name="task" value="portaluser.remove">
                                    <input type="hidden" name="id" value="<?php echo (int) $person->id; ?>">
                                    <input type="hidden" name="access_id" value="<?php echo (int) $entry->access_id; ?>">
                                    <?php echo $token; ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_REMOVE'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if ($this->canEdit) : ?>
<section class="card card-body" id="grantAccess">
    <h3 class="h5"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_GRANT'); ?></h3>
    <?php if ((int) $person->block === 1) : ?>
        <p class="mb-0 ms-muted"><?php echo Text::_('COM_MOTHERSHIP_ACCESS_ERROR_BLOCKED'); ?></p>
    <?php elseif (!$this->grantableClients) : ?>
        <p class="mb-0 ms-muted"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_GRANT_NONE'); ?></p>
    <?php else : ?>
        <p class="ms-muted"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_GRANT_DESC'); ?></p>
        <form method="post" action="<?php echo $action; ?>">
            <div class="mb-3">
                <label class="form-label" for="grant-client"><?php echo Text::_('COM_MOTHERSHIP_FILTER_CLIENT_LABEL'); ?></label>
                <select class="form-select" id="grant-client" name="client_id" required>
                    <option value=""><?php echo Text::_('COM_MOTHERSHIP_FILTER_SELECT_CLIENT'); ?></option>
                    <?php foreach ($this->grantableClients as $id => $name) : ?>
                        <option value="<?php echo (int) $id; ?>"><?php echo $e($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="mb-0"><span class="badge bg-primary-subtle text-primary-emphasis"><?php echo $e(ClientAccess::roleLabel(ClientAccess::ROLE_FULL)); ?></span> <span class="text-body-secondary"><?php echo $e(ClientAccess::roleDescription(ClientAccess::ROLE_FULL)); ?></span></p>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="grant-on-behalf" name="on_behalf_of_owner" value="1" checked>
                <label class="form-check-label" for="grant-on-behalf"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_GRANT_ON_BEHALF'); ?></label>
            </div>
            <input type="hidden" name="task" value="portaluser.grant">
            <input type="hidden" name="id" value="<?php echo (int) $person->id; ?>">
            <?php echo $token; ?>
            <button type="submit" class="btn btn-primary mt-3"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSER_GRANT_BUTTON'); ?></button>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>
