<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\InvoiceHelper;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Invoices\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=invoices'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="invoiceList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_NUMBER', 'i.number', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_PDF', 'c.name', $listDirn, $listOrder); ?>
                                </th>
        
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_ACCOUNT', 'a.name', $listDirn, $listOrder); ?>
                                </th>
                                
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_TOTAL', 'i.total', $listDirn, $listOrder); ?>
                                </th>
                                
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_STATUS', 'i.status', $listDirn, $listOrder); ?>
                                </th>

                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_DUE', 'i.due', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_INVOICE_HEADING_CREATED', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item): 
                                $user = Factory::getApplication()->getIdentity();
                                $canEdit = $user->authorise('core.edit', "com_mothership.invoice.{$item->id}");
                                $canEditOwn = $user->authorise('core.edit.own', "com_mothership.invoice.{$item->id}");
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
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=invoice.edit&id={$item->id}"); ?>"><?php echo (int) $item->number; ?></a>
                                    </td>
                                    <td>
                                        <a class="downloadPdf" href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.downloadPdf&id=' . (int) $item->id); ?>" target="_blank">
                                            <i class="fa-solid fa-file-pdf" aria-hidden="true" title="<?php echo Text::_('COM_MOTHERSHIP_DOWNLOAD_PDF'); ?>"></i>
                                        </a>
                                        <a class="previewPdf" href="<?php echo Route::_('index.php?option=com_mothership&task=invoice.previewPdf&id=' . (int) $item->id); ?>" target="_blank">
                                            <i class="fa-solid fa-eye" aria-hidden="true" title="<?php echo Text::_('COM_MOTHERSHIP_PREVIEW_PDF'); ?>"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=invoices'))) ?>" ><?php echo htmlspecialchars($item->client_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_("index.php?option=com_mothership&task=account.edit&id={$item->account_id}&return=" . base64_encode(Route::_('index.php?option=com_mothership&view=invoices'))) ?>" ><?php echo htmlspecialchars($item->account_name, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </td>
                                    <td>
                                        $<?php echo number_format($item->total, 2, '.', ','); ?>
                                    </td>
                                    <td>
                                        <?php echo $item->status; ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($item->due) ? $item->due : 'N/A'; ?>
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