<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\Mothership\Administrator\View\Logs\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=logs'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="logList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'l.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_CLIENT_NAME', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_ACCOUNT_NAME', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_DESCRIPTION', 'l.description', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_DETAILS', 'l.details', $listDirn, $listOrder); ?>
                                </th>                                
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_OBJECT_TYPE', 'l.object_type', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_OBJECT_ID', 'l.object_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_LOGS_HEADING_CREATED', 'l.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item): 
                                $user = Factory::getApplication()->getIdentity();
                                $canEdit = $user->authorise('core.edit', "com_mothership.log.{$item->id}");
                                $canEditOwn = $user->authorise('core.edit.own', "com_mothership.log.{$item->id}");
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
                                        <?php echo $item->client_name; ?>
                                    </td>
                                    <td>
                                        <?php echo $item->account_name; ?>
                                    </td>
                                    <td>
                                        <?php 

                                        if($item->action == 'payment_status_changed'){
                                            $meta = json_decode(json_decode($item->meta));
                                            echo sprintf(Text::_('COM_MOTHERSHIP_LOG_PAYMENT_STATUS_CHANGED'), $meta->old_status, $meta->new_status);                                        
                                        }
                                        else if($item->action == 'viewed' && $item->object_type == 'payment'){
                                            echo sprintf(Text::_('COM_MOTHERSHIP_LOG_PAYMENT_VIEWED'), $item->object_id);                                        
                                        }
                
                                        ?>
                                    </td>
                                    <td>
                                    <?php 
                                        if($item->action == 'payment_status_changed'){
                                            $meta = json_decode(json_decode($item->meta));
                                            echo sprintf(Text::_('COM_MOTHERSHIP_LOG_PAYMENT_STATUS_CHANGED_DESC'), $item->object_id,$meta->old_status, $meta->new_status, $item->user_id);
                                        }
                                        else if($item->action == 'viewed' && $item->object_type == 'payment'){
                                            echo sprintf(Text::_('COM_MOTHERSHIP_LOG_PAYMENT_VIEWED_DESC'), $item->object_id, $item->user_id);                                        
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $item->object_type; ?>
                                    </td>
                                    <td>
                                        <?php echo $item->object_id; ?>
                                    </td>
                                    <td>
                                        <?php echo $item->created; ?>
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