<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/** @var \Joomla\Component\Mothership\Administrator\View\Projects\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=projects'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="projectList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PROJECT_HEADING_NAME', 'p.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PROJECT_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PROJECT_HEADING_ACCOUNT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PROJECT_HEADING_CREATED', 'p.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PROJECT_HEADING_TYPE', 'p.type', $listDirn, $listOrder); ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item): 
                                $user = Factory::getApplication()->getIdentity();
                                $canEdit = $user->authorise('core.edit', "com_mothership.project.{$item->id}");
                                $canEditOwn = $user->authorise('core.edit.own', "com_mothership.project.{$item->id}");
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
                                        <?php $metadata = json_decode($item->metadata, true); ?>
                                        <?php if ($item->checked_out) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'articles.', $canCheckin); ?>
                                        <?php endif; ?>

                                        <?php if($metadata['status'] == 'online'):?>
                                            <span class="badge bg-success" title="<?php echo Text::_('COM_MOTHERSHIP_PROJECT_STATUS_ONLINE'); ?>" style="border-radius: 50%; display: inline-block; width: 10px; height: 10px;"></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger" title="<?php echo Text::_('COM_MOTHERSHIP_PROJECT_STATUS_OFFLINE'); ?>" style="border-radius: 50%; display: inline-block; width: 10px; height: 10px;"></span>
                                        <?php endif; ?>

                                        <?php if ($canEdit || $canEditOwn) : ?>
                                            <a href="<?= Route::_("index.php?option=com_mothership&task=project.edit&id={$item->id}") ?>" title="<?= Text::_('JACTION_EDIT') ?> <?= $this->escape($item->name) ?>">
                                                <?= $this->escape($item->name) ?></a>
                                        <?php else : ?>
                                            <span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>"><?php echo $this->escape($item->name); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=projects'))) ?>" ><?php echo htmlspecialchars($item->client_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=account.edit&id={$item->account_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=projects'))) ?>" ><?php echo htmlspecialchars($item->account_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo strtoupper($item->type); 
                                        $metadata = json_decode($item->metadata, true);
                                        if($item->type=="website"){
                                            echo "<br/><small>";
                                            echo $metadata['primary_url']."<br/>";
                                            echo "".$metadata['cms_type']." ";
                                            echo $metadata['cms_version']."</small><br/>";
                                        }
                                        ?>
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