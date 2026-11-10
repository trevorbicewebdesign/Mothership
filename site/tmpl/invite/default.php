<?php
/**
 * Invite acceptance: an invited person sets their password and is signed in.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="mothership-invite" style="max-width: 460px; margin: 0 auto;">
    <?php if (!$this->invite) : ?>
        <h1><?php echo Text::_('COM_MOTHERSHIP_INVITE_EXPIRED_TITLE'); ?></h1>
        <p><?php echo Text::plural('COM_MOTHERSHIP_INVITE_EXPIRED_DESC', ClientAccess::INVITE_DAYS); ?></p>
        <p><?php echo Text::sprintf('COM_MOTHERSHIP_INVITE_EXPIRED_SIGN_IN', Route::_('index.php?option=com_users&view=login')); ?></p>
    <?php else : ?>
        <h1><?php echo Text::sprintf('COM_MOTHERSHIP_INVITE_WELCOME', $e(strtok((string) $this->invite->name, ' '))); ?></h1>
        <p><?php echo Text::sprintf('COM_MOTHERSHIP_INVITE_INTRO', $e(ClientAccess::roleLabel((string) $this->invite->role)), $e($this->invite->client_name)); ?></p>
        <form method="post" action="<?php echo Route::_('index.php?option=com_mothership&task=invite.accept'); ?>" id="invite-form">
            <div class="mb-3">
                <label class="form-label" for="invite-email"><?php echo Text::_('JGLOBAL_EMAIL'); ?></label>
                <input class="form-control" type="email" id="invite-email" value="<?php echo $e($this->invite->email); ?>" readonly autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label" for="invite-password"><?php echo Text::_('JGLOBAL_PASSWORD'); ?></label>
                <input class="form-control" type="password" id="invite-password" name="password" required minlength="<?php echo (int) $this->minLength; ?>" autocomplete="new-password" aria-describedby="invite-password-help">
                <div class="form-text" id="invite-password-help"><?php echo Text::plural('COM_MOTHERSHIP_INVITE_PASSWORD_HINT', (int) $this->minLength); ?></div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="invite-password2"><?php echo Text::_('COM_MOTHERSHIP_INVITE_PASSWORD_CONFIRM'); ?></label>
                <input class="form-control" type="password" id="invite-password2" name="password2" required minlength="<?php echo (int) $this->minLength; ?>" autocomplete="new-password">
            </div>
            <input type="hidden" name="token" value="<?php echo $e($this->token); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_MOTHERSHIP_INVITE_SUBMIT'); ?></button>
        </form>
    <?php endif; ?>
</div>
