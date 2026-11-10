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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\EmailLog;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Emails\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

// Muted text uses opacity, not Atum's --template-text-light, which is white (see the logs list).
$wa->addInlineStyle(<<<'CSS'
#emailList .ms-email-muted { opacity: .72; }
#emailList .ms-email-when { white-space: nowrap; font-variant-numeric: tabular-nums; }
#emailList .ms-email-why { font-size: .8125rem; margin-top: .2rem; }
#emailList .ms-email-why.is-failed { color: var(--danger, #c00); opacity: 1; }
#emailList .ms-email-subject { font-weight: 500; }
CSS);

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=emails'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="emailList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_MOTHERSHIP_MANAGER_EMAILS'); ?></caption>
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-1">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_STATUS', 'e.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_SUBJECT', 'e.subject', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_TO', 'e.recipient', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo Text::_('COM_MOTHERSHIP_EMAILS_HEADING_ABOUT'); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_SENT_BY', 'u.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EMAILS_HEADING_WHEN', 'e.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                $link  = Route::_('index.php?option=com_mothership&view=email&id=' . (int) $item->id);
                                $about = EmailLog::aboutLabel($item);
                                $aboutLink = EmailLog::aboutLink($item);
                                $who   = $item->user_name ?: ($item->user_username ?: Text::_('COM_MOTHERSHIP_EMAIL_BY_SITE'));
                                $when  = $item->status === EmailLog::STATUS_SENT && !empty($item->sent_at) ? $item->sent_at : $item->created;
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td><?php echo EmailLog::pill((string) $item->status); ?></td>
                                    <td>
                                        <a class="ms-email-subject" href="<?php echo $link; ?>"><?php echo $e($item->subject); ?></a>
                                        <?php if ($item->status === EmailLog::STATUS_FAILED && $item->error !== null && $item->error !== '') : ?>
                                            <div class="ms-email-why is-failed"><?php echo $e($item->error); ?></div>
                                        <?php elseif ($item->status === EmailLog::STATUS_SKIPPED && $item->error !== null && $item->error !== '') : ?>
                                            <div class="ms-email-why ms-email-muted"><?php echo $e($item->error); ?></div>
                                        <?php elseif (!empty($item->template)) : ?>
                                            <div class="ms-email-why ms-email-muted"><?php echo $e($item->template); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $e($item->recipient); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if ($about !== '' && $aboutLink !== '') : ?>
                                            <a href="<?php echo Route::_($aboutLink); ?>"><?php echo $e($about); ?></a>
                                        <?php else : ?>
                                            <?php echo $e($about); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><?php echo $e($item->client_name); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo $e($who); ?></td>
                                    <td class="ms-email-when">
                                        <?php echo HTMLHelper::_('date', $when, 'M j, Y'); ?>
                                        <div class="small ms-email-muted"><?php echo HTMLHelper::_('date', $when, 'g:i A'); ?></div>
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
