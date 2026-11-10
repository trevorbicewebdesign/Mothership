<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Email\HtmlView $this */

$item = $this->item;
$e    = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$when = static fn ($d) => $d ? HTMLHelper::_('date', $d, 'M j, Y g:i:s A') : '';

$about     = EmailLog::aboutLabel($item);
$aboutLink = EmailLog::aboutLink($item);
$who       = $item->user_name ?: ($item->user_username ?: Text::_('COM_MOTHERSHIP_EMAIL_BY_SITE'));

$wa = $this->getDocument()->getWebAssetManager();
$wa->addInlineStyle(<<<'CSS'
#ms-email .ms-email-facts dt { font-weight: 500; opacity: .72; }
#ms-email .ms-email-facts dd { margin-bottom: .5rem; word-break: break-word; }
#ms-email .ms-email-body { width: 100%; min-height: 24rem; border: 1px solid var(--border-color, #ccc); background: #fff; border-radius: .25rem; }
#ms-email .ms-email-subject { font-size: 1.25rem; font-weight: 500; margin: 0; }
CSS);

// The body is shown in a frame with no scripts, sized to its content once it has loaded.
$wa->addInlineScript(<<<'JS'
document.addEventListener('DOMContentLoaded', () => {
  const frame = document.getElementById('ms-email-body');
  if (!frame) { return; }
  const fit = () => {
    try { frame.style.height = Math.max(384, frame.contentDocument.documentElement.scrollHeight + 24) + 'px'; } catch (e) {}
  };
  frame.addEventListener('load', fit);
  fit();
});
JS);

$facts = [
    'COM_MOTHERSHIP_EMAIL_DETAILS_TO'       => $e($item->recipient),
    'COM_MOTHERSHIP_EMAIL_DETAILS_CC'       => $e($item->cc),
    'COM_MOTHERSHIP_EMAIL_DETAILS_BCC'      => $e($item->bcc),
    'COM_MOTHERSHIP_EMAIL_DETAILS_REPLY_TO' => $e($item->reply_to),
    'COM_MOTHERSHIP_EMAIL_DETAILS_ABOUT'    => $about === '' ? '' : ($aboutLink !== '' ? '<a href="' . Route::_($aboutLink) . '">' . $e($about) . '</a>' : $e($about)),
    'COM_MOTHERSHIP_EMAIL_DETAILS_CLIENT'   => $e($item->client_name),
    'COM_MOTHERSHIP_EMAIL_DETAILS_ACCOUNT'  => $e($item->account_name),
    'COM_MOTHERSHIP_EMAIL_DETAILS_TEMPLATE' => $e($item->template),
    'COM_MOTHERSHIP_EMAIL_DETAILS_SENT_BY'  => $e($who),
    'COM_MOTHERSHIP_EMAIL_DETAILS_CREATED'  => $when($item->created),
    'COM_MOTHERSHIP_EMAIL_DETAILS_SENT_AT'  => $when($item->sent_at),
    'COM_MOTHERSHIP_EMAIL_DETAILS_MESSAGE_ID' => $e($item->message_id),
    'COM_MOTHERSHIP_EMAIL_DETAILS_RESEND_OF' => $item->resend_of
        ? '<a href="' . Route::_('index.php?option=com_mothership&view=email&id=' . (int) $item->resend_of) . '">#' . (int) $item->resend_of . '</a>'
        : '',
];
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=email&id=' . (int) $item->id); ?>" method="post" name="adminForm" id="adminForm">
    <div class="main-card p-4" id="ms-email">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <?php echo EmailLog::pill((string) $item->status); ?>
            <h2 class="ms-email-subject"><?php echo $e($item->subject); ?></h2>
            <span class="ms-email-muted small">#<?php echo (int) $item->id; ?></span>
        </div>

        <?php if ($item->status === EmailLog::STATUS_FAILED) : ?>
            <div class="alert alert-danger">
                <strong><?php echo Text::_('COM_MOTHERSHIP_EMAIL_ERROR_HEADING'); ?></strong>
                <?php echo $e($item->error !== null && $item->error !== '' ? $item->error : Text::_('COM_MOTHERSHIP_EMAIL_ERROR_UNKNOWN')); ?>
            </div>
        <?php elseif ($item->status === EmailLog::STATUS_SKIPPED) : ?>
            <div class="alert alert-secondary"><?php echo $e($item->error); ?></div>
        <?php elseif ($item->status === EmailLog::STATUS_PENDING) : ?>
            <div class="alert alert-warning"><?php echo Text::_('COM_MOTHERSHIP_EMAIL_STATUS_PENDING_DESC'); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <dl class="ms-email-facts">
                    <?php foreach ($facts as $label => $value) : ?>
                        <?php if ($value === '') : continue; endif; ?>
                        <dt><?php echo Text::_($label); ?></dt>
                        <dd><?php echo $value; ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
            <div class="col-lg-8">
                <h3 class="h6 ms-email-muted">
                    <?php echo Text::_($item->status === EmailLog::STATUS_SENT ? 'COM_MOTHERSHIP_EMAIL_BODY_SENT' : 'COM_MOTHERSHIP_EMAIL_BODY_UNSENT'); ?>
                </h3>
                <?php if ((string) $item->body_html === '') : ?>
                    <p class="ms-email-muted"><?php echo Text::_('COM_MOTHERSHIP_EMAIL_BODY_EMPTY'); ?></p>
                <?php else : ?>
                    <iframe id="ms-email-body" class="ms-email-body" sandbox="allow-same-origin" title="<?php echo Text::_('COM_MOTHERSHIP_EMAIL_BODY_SENT'); ?>" srcdoc="<?php echo $e($item->body_html); ?>"></iframe>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
