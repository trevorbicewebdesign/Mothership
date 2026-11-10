<?php
/**
 * Client edit, Users tab: who besides the owner can use this client's portal.
 *
 * Its inputs sit inside the client form, so they carry no jform names and are posted by
 * buttons that build their own small form (data-access-task) instead of submitting the page.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Client\HtmlView $this */

$clientId = (int) $this->item->id;
$members  = ClientAccess::members($clientId);
$owner    = $members && $members[0]->role === ClientAccess::ROLE_OWNER ? $members[0] : null;
$canEdit  = (bool) $this->canDo->get('core.edit');
$e        = fn ($v) => $this->escape((string) $v);
?>
<div id="clientUsers">
<p class="text-muted"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_INTRO'); ?></p>

<?php // columns-order-ignore keeps Joomla's column chooser, which the client form loads, off this table. ?>
<table class="table columns-order-ignore" id="clientUserList">
    <thead>
        <tr>
            <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_NAME'); ?></th>
            <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_ROLE'); ?></th>
            <th scope="col"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_STATUS'); ?></th>
            <th scope="col" class="text-end"><span class="visually-hidden"><?php echo Text::_('JACTION'); ?></span></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($members as $m) : $isOwner = $m->role === ClientAccess::ROLE_OWNER; ?>
        <tr>
            <td>
                <a href="<?php echo Route::_('index.php?option=com_mothership&view=portaluser&id=' . (int) $m->user_id); ?>"><?php echo $e($m->name); ?></a>
                <br><small class="text-muted"><?php echo $e($m->email); ?></small>
            </td>
            <td><span class="badge <?php echo $isOwner ? 'bg-primary' : ($m->role === ClientAccess::ROLE_FULL ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary'); ?>"><?php echo $e(ClientAccess::roleLabel($m->role)); ?></span></td>
            <td>
                <?php if ($m->invite_expired) : ?>
                    <span class="badge bg-danger-subtle text-danger-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_EXPIRED'); ?></span>
                <?php elseif ($m->pending) : ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_PENDING'); ?></span>
                <?php else : ?>
                    <span class="badge bg-success-subtle text-success-emphasis"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_ACTIVE'); ?></span>
                <?php endif; ?>
                <?php if ($m->pending && $m->invite_error) : ?>
                    <div class="small text-danger"><?php echo Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_EMAIL_FAILED', $e($m->invite_error)); ?></div>
                <?php elseif ($m->pending && $m->invite_sent_at) : ?>
                    <div class="small text-muted"><?php echo Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_EMAILED', HTMLHelper::_('date', $m->invite_sent_at, Text::_('DATE_FORMAT_LC2'))); ?></div>
                <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
                <?php if (!$isOwner && $canEdit) : ?>
                    <?php if ($m->pending) : ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-access-task="resendInvite" data-access-id="<?php echo (int) $m->access_id; ?>"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_RESEND'); ?></button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-access-task="removeUser" data-access-id="<?php echo (int) $m->access_id; ?>" data-confirm="<?php echo $e(Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_REMOVE_CONFIRM', $m->name)); ?>"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_REMOVE'); ?></button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($canEdit) : ?>
<fieldset class="options-form" data-access-scope>
    <legend><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_INVITE'); ?></legend>
    <p class="text-muted"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_INVITE_DESC'); ?></p>
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <label class="form-label" for="access-name"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_NAME'); ?></label>
            <input class="form-control" type="text" id="access-name" data-field="name" autocomplete="off">
        </div>
        <div class="col-md-7">
            <label class="form-label" for="access-email"><?php echo Text::_('JGLOBAL_EMAIL'); ?></label>
            <input class="form-control" type="email" id="access-email" data-field="email" autocomplete="off">
        </div>
    </div>
    <p class="mb-0"><span class="badge bg-primary-subtle text-primary-emphasis"><?php echo $e(ClientAccess::roleLabel(ClientAccess::ROLE_FULL)); ?></span> <span class="text-body-secondary"><?php echo $e(ClientAccess::roleDescription(ClientAccess::ROLE_FULL)); ?></span></p>
    <?php if ($owner) : ?>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="access-on-behalf" value="1" data-field="on_behalf_of_owner" checked>
            <label class="form-check-label" for="access-on-behalf"><?php echo Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_ON_BEHALF', $e($owner->name)); ?></label>
            <div class="form-text"><?php echo Text::sprintf('COM_MOTHERSHIP_CLIENT_USERS_ON_BEHALF_DESC', $e($owner->name)); ?></div>
        </div>
    <?php endif; ?>
    <button type="button" class="btn btn-primary mt-3" id="access-invite" data-access-task="inviteUser"><?php echo Text::_('COM_MOTHERSHIP_CLIENT_USERS_SEND_INVITE'); ?></button>
</fieldset>
<?php endif; ?>
</div>

<script>
(function () {
    var action = <?php echo json_encode(Route::_('index.php?option=com_mothership', false)); ?>;
    var token = <?php echo json_encode(Session::getFormToken()); ?>;
    var clientId = <?php echo $clientId; ?>;
    var tab = document.getElementById('clientUsers');

    // Enter in the name or email field would otherwise submit the client form around it.
    tab.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.matches('[data-field]')) {
            event.preventDefault();
            document.getElementById('access-invite').click();
        }
    });

    tab.addEventListener('click', function (event) {
        var button = event.target.closest('[data-access-task]');
        if (!button || (button.dataset.confirm && !window.confirm(button.dataset.confirm))) {
            return;
        }

        var form = document.createElement('form');
        form.method = 'post';
        form.action = action;
        var add = function (name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };

        add('task', 'client.' + button.dataset.accessTask);
        add('id', clientId);
        add(token, '1');
        if (button.dataset.accessId) {
            add('access_id', button.dataset.accessId);
        }

        var scope = button.closest('[data-access-scope]');
        if (scope) {
            scope.querySelectorAll('[data-field]').forEach(function (el) {
                if (el.type === 'checkbox' && !el.checked) {
                    return;
                }
                add(el.dataset.field, el.value);
            });
        }

        document.body.appendChild(form);
        form.submit();
    });
})();
</script>
