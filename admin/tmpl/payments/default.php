<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/** @var \Joomla\Component\Mothership\Administrator\View\Payments\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=payments'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                // Search tools bar
                echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);
                ?>
                <?php if (empty($this->items)): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else: ?>
                    <table class="table itemList" id="paymentList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_DATE', 'p.payment_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_AMOUNT', 'p.amount', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_METHOD', 'p.payment_method', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_ACCOUNT', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_CREATED_AT', 'p.created_at', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_PAYMENT_HEADING_STATUS', 'p.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5">
                                    <?php echo Text::_('COM_MOTHERSHIP_PAYMENT_HEADING_ALLOCATIONS'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item):
                                $user       = Factory::getApplication()->getIdentity();
                                $canEdit    = $user->authorise('core.edit', "com_mothership.payment.{$item->id}");
                                $canEditOwn = $user->authorise('core.edit.own', "com_mothership.payment.{$item->id}");
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
                                        <?php echo HTMLHelper::_('date', $item->payment_date, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($item->amount, 2); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->payment_method); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=payments'))); ?>">
                                            <?php echo htmlspecialchars($item->client_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=account.edit&id={$item->account_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=payments'))); ?>">
                                            <?php echo htmlspecialchars($item->account_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->created_at, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                    <td>
                                        <?php echo $item->status; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&view=invoicepayments&payment_id={$item->id}"); ?>" title="<?php echo Text::_('COM_MOTHERSHIP_PAYMENT_MANAGE_ALLOCATIONS'); ?>">
                                            <?php echo Text::_('COM_MOTHERSHIP_PAYMENT_MANAGE_ALLOCATIONS'); ?>
                                        </a>
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
