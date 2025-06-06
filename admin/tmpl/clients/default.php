<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\Mothership\Administrator\View\Clients\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=clients'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                // Search tools bar
                echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
                ?>
                <?php if (empty($this->items)): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span
                            class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else: ?>
                    <table class="table itemList" id="clientList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_CLIENT_HEADING_NAME', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_CLIENT_HEADING_PHONE', 'a.phone', $listDirn, $listOrder); ?>
                                </th>                                
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_CLIENT_HEADING_DEFAULT_RATE', 'a.default_rate', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_CLIENT_HEADING_CREATED', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item): 
                                $user = Factory::getApplication()->getIdentity();
                                $canEdit = $user->authorise('core.edit', "com_mothership.client.{$item->id}");
                                $canEditOwn = $user->authorise('core.edit.own', "com_mothership.client.{$item->id}");
                                $canCheckin = $user->authorise('core.manage', 'com_mothership');
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                    <td>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor ?? '', $item->checked_out_time, 'articles.', $canCheckin); ?>
                                        <?php endif; ?>

                                        <?php if ($canEdit || $canEditOwn) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_mothership&task=client.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->name); ?>">
                                                <?php echo $this->escape($item->name); ?></a>
                                        <?php else : ?>
                                            <span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>"><?php echo $this->escape($item->name); ?></span>
                                        <?php endif; ?>
      
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($item->phone, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        $<?php echo number_format($item->default_rate, 2); ?>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
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