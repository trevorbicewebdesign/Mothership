<?php
// Sent when someone is given access to a client's portal. A new login gets a one-time link to
// set a password; someone who already had a login is just told.
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$e       = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$first   = trim((string) strtok((string) ($displayData['name'] ?? ''), ' '));
$client  = $e($displayData['client_name'] ?? '');
$role    = $e($displayData['role_label'] ?? '');
$desc    = $e($displayData['role_desc'] ?? '');
$inviter = $e(($displayData['inviter'] ?? '') !== '' ? $displayData['inviter'] : Text::_('COM_MOTHERSHIP_ACCESS_EMAIL_SOMEONE'));
$link    = $e($displayData['link'] ?? '');
$days    = (int) ($displayData['days'] ?? 7);
?>
<p><?php echo $first !== '' ? Text::sprintf('COM_MOTHERSHIP_ACCESS_EMAIL_HELLO_NAME', $e($first)) : Text::_('COM_MOTHERSHIP_ACCESS_EMAIL_HELLO'); ?></p>
<?php if (!empty($displayData['is_new'])) : ?>
<p><?php echo Text::sprintf('COM_MOTHERSHIP_ACCESS_EMAIL_INVITED', $inviter, $client, $role); ?></p>
<p><?php echo $desc; ?></p>
<p><a href="<?php echo $link; ?>"><?php echo Text::_('COM_MOTHERSHIP_ACCESS_EMAIL_SET_PASSWORD'); ?></a></p>
<p><?php echo Text::plural('COM_MOTHERSHIP_ACCESS_EMAIL_EXPIRES', $days); ?></p>
<?php else : ?>
<p><?php echo Text::sprintf('COM_MOTHERSHIP_ACCESS_EMAIL_GIVEN', $inviter, $role, $client); ?></p>
<p><?php echo $desc; ?></p>
<p><?php echo Text::sprintf('COM_MOTHERSHIP_ACCESS_EMAIL_SIGN_IN', $link); ?></p>
<?php endif; ?>
