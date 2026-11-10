<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * The Email tab on an invoice, payment or proposal: every message the site tried to send
 * about this record, including the ones that failed or were skipped.
 *
 * Display data: items (from EmailLog::forObject()).
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;

$items = $displayData['items'] ?? [];
$e     = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="row" id="ms-email-tab">
    <div class="col-lg-10">
        <?php if (empty($items)) : ?>
            <div class="alert alert-info"><?php echo Text::_('COM_MOTHERSHIP_EMAIL_TAB_NONE'); ?></div>
        <?php else : ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="w-1"><?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_STATUS'); ?></th>
                        <th><?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_WHEN'); ?></th>
                        <th><?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_TO'); ?></th>
                        <th><?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_SUBJECT'); ?></th>
                        <th class="d-none d-md-table-cell"><?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_SENT_BY'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item) :
                    $when = $item->status === EmailLog::STATUS_SENT && !empty($item->sent_at) ? $item->sent_at : $item->created;
                    $who  = $item->user_name ?: ($item->user_username ?: Text::_('COM_MOTHERSHIP_EMAIL_BY_SITE'));
                    ?>
                    <tr>
                        <td><?php echo EmailLog::pill((string) $item->status); ?></td>
                        <td style="white-space:nowrap; font-variant-numeric:tabular-nums;"><?php echo HTMLHelper::_('date', $when, 'M j, Y g:i A'); ?></td>
                        <td><?php echo $e($item->recipient); ?></td>
                        <td>
                            <a href="<?php echo Route::_('index.php?option=com_mothership&view=email&id=' . (int) $item->id); ?>"><?php echo $e($item->subject); ?></a>
                            <?php if ($item->status !== EmailLog::STATUS_SENT && $item->error !== null && $item->error !== '') : ?>
                                <div class="small <?php echo $item->status === EmailLog::STATUS_FAILED ? 'text-danger' : 'text-muted'; ?>"><?php echo $e($item->error); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell"><?php echo $e($who); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="small text-muted"><?php echo Text::_('COM_MOTHERSHIP_EMAIL_TAB_NOTE'); ?></p>
    </div>
</div>
