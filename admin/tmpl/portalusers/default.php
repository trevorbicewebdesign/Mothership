<?php
/**
 * Portal Users: a directory of everyone who can use a client portal. One row per person with a
 * count per role; the person's page lists the clients themselves. Access is given per client,
 * on the client's Users tab or the person's page.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Portalusers\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$roleBadge = [
    ClientAccess::ROLE_OWNER => 'bg-primary',
    ClientAccess::ROLE_FULL  => 'bg-primary-subtle text-primary-emphasis',
];
?>
<style>
#portalUserList .ms-roles { display:flex; flex-wrap:wrap; gap:6px; }
#portalUserList .ms-roles .badge { font-weight:600; }
#portalUserList .ms-roles .badge b { font-weight:700; margin-left:4px; padding-left:6px; border-left:1px solid currentColor; }
#portalUserList .ms-muted, #pendingInvites .ms-muted { opacity:.7; }
#pendingInvites { margin-bottom:1.5rem; }
#pendingInvites h2 { font-size:1.1rem; margin:0 0 .75rem; }
#pendingInvites td, #pendingInvites th { vertical-align:middle; }
</style>

<?php if ($this->pending) : ?>
    <section id="pendingInvites" class="card card-body">
        <h2><?php echo Text::sprintf('COM_MOTHERSHIP_PORTALUSERS_PENDING_TITLE', \count($this->pending)); ?></h2>
        <?php // columns-order-ignore keeps Joomla's column chooser (meant for the people list) off this table. ?>
        <table class="table mb-0 columns-order-ignore">
            <thead>
                <tr>
                    <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_NAME'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_FILTER_CLIENT_LABEL'); ?></th>
                    <th scope="col" class="d-none d-lg-table-cell"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_HEADING_INVITED_BY'); ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_HEADING_SENT'); ?></th>
                    <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_HEADING_EXPIRES'); ?></th>
                    <th scope="col" class="text-end"><span class="visually-hidden"><?php echo Text::_('JACTION'); ?></span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->pending as $invite) : ?>
                <tr>
                    <td>
                        <a href="<?php echo Route::_('index.php?option=com_mothership&view=portaluser&id=' . (int) $invite->user_id); ?>"><?php echo $this->escape($invite->name); ?></a>
                        <div class="small ms-muted"><?php echo $this->escape($invite->email); ?></div>
                    </td>
                    <td><a href="<?php echo Route::_('index.php?option=com_mothership&task=client.edit&id=' . (int) $invite->client_id); ?>"><?php echo $this->escape($invite->client_name); ?></a></td>
                    <td class="d-none d-lg-table-cell">
                        <?php echo $invite->sent_by ? $this->escape($invite->sent_by) : '&mdash;'; ?>
                        <?php if ($invite->on_behalf_of) : ?>
                            <div class="small ms-muted"><?php echo Text::sprintf('COM_MOTHERSHIP_PORTALUSERS_ON_BEHALF_OF', $this->escape($invite->on_behalf_of)); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <?php echo $invite->created ? HTMLHelper::_('date', $invite->created, Text::_('DATE_FORMAT_LC4')) : '&mdash;'; ?>
                        <?php if ($invite->invite_error) : ?>
                            <div class="small text-danger"><?php echo Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_EMAIL_FAILED', $this->escape($invite->invite_error)); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($invite->invite_expired) : ?>
                            <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_EXPIRED'); ?></span>
                        <?php elseif ($invite->invite_expires) : ?>
                            <?php echo HTMLHelper::_('date', $invite->invite_expires, Text::_('DATE_FORMAT_LC4')); ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($this->canEdit) : ?>
                            <form method="post" action="<?php echo Route::_('index.php?option=com_mothership'); ?>" class="d-inline">
                                <input type="hidden" name="task" value="portalusers.resendInvite">
                                <input type="hidden" name="access_id" value="<?php echo (int) $invite->access_id; ?>">
                                <?php echo HTMLHelper::_('form.token'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_RESEND'); ?></button>
                            </form>
                            <form method="post" action="<?php echo Route::_('index.php?option=com_mothership'); ?>" class="d-inline" onsubmit="return confirm(<?php echo $this->escape(json_encode(Text::sprintf('COM_MOTHERSHIP_PORTALUSERS_CANCEL_CONFIRM', $invite->email, $invite->client_name))); ?>);">
                                <input type="hidden" name="task" value="portalusers.cancelInvite">
                                <input type="hidden" name="access_id" value="<?php echo (int) $invite->access_id; ?>">
                                <?php echo HTMLHelper::_('form.token'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_CANCEL_INVITE'); ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=portalusers'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList" id="portalUserList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_MOTHERSHIP_MANAGER_PORTALUSERS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_CLIENT_USERS_NAME', 'name', $listDirn, $listOrder); ?></th>
                                <th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PORTALUSERS_HEADING_CLIENTS', 'client_count', $listDirn, $listOrder); ?></th>
                                <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_ROLE'); ?></th>
                                <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_HEADING_INVITES'); ?></th>
                                <th scope="col" class="d-none d-md-table-cell w-15"><?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PORTALUSERS_HEADING_LAST_VISIT', 'last_visit', $listDirn, $listOrder); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) :
                            $personUrl = Route::_('index.php?option=com_mothership&view=portaluser&id=' . (int) $item->user_id);
                        ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, (int) $item->user_id, false, 'cid', 'cb', $item->name); ?></td>
                                <th scope="row">
                                    <a href="<?php echo $personUrl; ?>"><?php echo $this->escape($item->name); ?></a>
                                    <div class="small ms-muted"><?php echo $this->escape($item->email); ?></div>
                                    <?php if (strcasecmp((string) $item->username, (string) $item->email) !== 0) : ?>
                                        <div class="small ms-muted"><?php echo Text::sprintf('COM_MOTHERSHIP_PORTALUSERS_USERNAME', $this->escape($item->username)); ?></div>
                                    <?php endif; ?>
                                    <?php if ((int) $item->block === 1) : ?>
                                        <span class="badge bg-danger"><?php echo Text::_('COM_MOTHERSHIP_PORTALUSERS_BLOCKED'); ?></span>
                                    <?php endif; ?>
                                </th>
                                <td>
                                    <?php if ($item->single_client_name !== null) : ?>
                                        <a href="<?php echo Route::_('index.php?option=com_mothership&task=client.edit&id=' . (int) $item->first_client_id); ?>"><?php echo $this->escape($item->single_client_name); ?></a>
                                    <?php else : ?>
                                        <a href="<?php echo $personUrl; ?>"><?php echo Text::plural('COM_MOTHERSHIP_PORTALUSER_N_CLIENTS', (int) $item->client_count); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="ms-roles">
                                        <?php foreach ($item->role_counts as $role => $n) : ?>
                                            <span class="badge <?php echo $roleBadge[$role] ?? 'bg-secondary'; ?>"><?php echo $this->escape(ClientAccess::roleLabel($role)); ?><b><?php echo (int) $n; ?></b></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php if ((int) $item->expired_count) : ?>
                                        <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_EXPIRED', (int) $item->expired_count); ?></span>
                                    <?php endif; ?>
                                    <?php if ((int) $item->pending_count) : ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_PENDING', (int) $item->pending_count); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $item->last_visit ? HTMLHelper::_('date', $item->last_visit, Text::_('DATE_FORMAT_LC4')) : Text::_('COM_MOTHERSHIP_PORTALUSERS_NEVER'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
