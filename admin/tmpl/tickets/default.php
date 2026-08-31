<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Tickets\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$statusMeta = [
    'new'         => ['New', '#e8f0fe', '#1a56db'],
    'open'        => ['Open', '#e8f0fe', '#1a56db'],
    'in_progress' => ['In Progress', '#fff4e5', '#b45309'],
    'waiting'     => ['Waiting', '#eef0f2', '#5a626c'],
    'resolved'    => ['Resolved', '#e6f4ea', '#137333'],
    'closed'      => ['Closed', '#eef0f2', '#5a626c'],
];
$prioMeta = [
    'low'    => ['Low', '#eef0f2', '#5a626c'],
    'normal' => ['Normal', '#e8f0fe', '#1a56db'],
    'high'   => ['High', '#fff4e5', '#b45309'],
    'urgent' => ['Urgent', '#fdecea', '#c81e1e'],
];
$pill = function ($meta, $key) {
    $m = $meta[$key] ?? [ucfirst(str_replace('_', ' ', (string) $key)), '#eef0f2', '#5a626c'];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:600;background:'
        . $m[1] . ';color:' . $m[2] . ';white-space:nowrap;">' . htmlspecialchars($m[0]) . '</span>';
};
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=tickets'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="ticketList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 't.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_SUBJECT', 't.subject', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_STATUS', 't.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_PRIORITY', 't.priority', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_ACCOUNT', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell"><?php echo Text::_('COM_MOTHERSHIP_TICKET_HEADING_REPLIES'); ?></th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_TICKET_HEADING_CREATED', 't.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                $user       = Factory::getApplication()->getIdentity();
                                $canEdit    = $user->authorise('core.edit', 'com_mothership');
                                $editLink   = Route::_("index.php?option=com_mothership&task=ticket.edit&id={$item->id}");
                                $clientLink = Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=tickets')));
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo (int) $item->id; ?></td>
                                    <td>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo $editLink; ?>"><strong><?php echo $this->escape($item->subject); ?></strong></a>
                                        <?php else : ?>
                                            <strong><?php echo $this->escape($item->subject); ?></strong>
                                        <?php endif; ?>
                                        <?php if (!empty($item->type)) : ?>
                                            <div class="small text-muted"><?php echo $this->escape($item->type); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pill($statusMeta, $item->status); ?></td>
                                    <td><?php echo $pill($prioMeta, $item->priority); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <a href="<?php echo $clientLink; ?>"><?php echo htmlspecialchars((string) $item->client_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?php echo htmlspecialchars((string) $item->account_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center d-none d-md-table-cell"><?php echo (int) $item->reply_count; ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?></td>
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
