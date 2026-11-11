<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Expenses\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$returnUrl = base64_encode(Route::_('index.php?option=com_mothership&view=expenses'));

// Light category colour pill (matches the invoices / payments lists).
$msPill = static function ($label, $tone) {
    $map = [
        'blue'   => ['#e8f0fe', '#1a56db'],
        'green'  => ['#e6f4ea', '#137333'],
        'amber'  => ['#fff4e5', '#b45309'],
        'red'    => ['#fdecea', '#c81e1e'],
        'purple' => ['#f3e8fd', '#6b21a8'],
        'teal'   => ['#e0f2f1', '#00695c'],
        'gray'   => ['#eef0f2', '#5a626c'],
    ];
    [$bg, $fg] = $map[$tone] ?? $map['gray'];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:600;'
        . 'background:' . $bg . ';color:' . $fg . ';white-space:nowrap;">' . htmlspecialchars((string) $label) . '</span>';
};

// Map each category to a tone and a human label.
$catTone = static fn ($c) => [
    'SOFTWARE' => 'blue', 'EQUIPMENT' => 'teal', 'WEBHOST' => 'purple', 'INTERNET' => 'purple',
    'CELL' => 'purple', 'ADVERTISING' => 'amber', 'FEE' => 'red', 'BANK' => 'red',
    'CONTRACT' => 'green', 'RENT' => 'amber', 'POWER' => 'amber', 'UTILITIES' => 'amber',
    'TRAVEL' => 'teal', 'TAXES' => 'red', 'OTHER' => 'gray',
][$c] ?? 'gray';
$catLabel = static fn ($c) => $c ? Text::_('COM_MOTHERSHIP_EXPENSE_CATEGORY_' . strtoupper($c)) : '';

$pageTotal = 0.0;
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=expenses'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else: ?>
                    <table class="table itemList" id="expenseList">
                        <thead>
                            <tr>
                                <th width="1%" class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'e.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_DATE', 'e.expense_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-20">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_ITEM', 'e.item', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_CATEGORY', 'e.category', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_MERCHANT', 'e.merchant', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-12">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_ACCOUNT', 'e.payment_account', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_HEADING_ASSIGNMENT'); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_HEADING_BILLABLE'); ?>
                                </th>
                                <th scope="col" class="w-10 text-end">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_EXPENSE_HEADING_AMOUNT', 'e.amount', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item):
                                $pageTotal += (float) $item->amount;
                                $editLink = Route::_("index.php?option=com_mothership&task=expense.edit&id={$item->id}&return={$returnUrl}");
                            ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <a href="<?php echo $editLink; ?>"><?php echo (int) $item->id; ?></a>
                                    </td>
                                    <td>
                                        <a href="<?php echo $editLink; ?>">
                                            <?php echo !empty($item->expense_date) && $item->expense_date !== '0000-00-00'
                                                ? HTMLHelper::_('date', $item->expense_date, Text::_('DATE_FORMAT_LC4'), null)
                                                : Text::_('COM_MOTHERSHIP_EXPENSE_NO_DATE'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo $editLink; ?>">
                                            <?php echo htmlspecialchars((string) ($item->item ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo $item->category ? $msPill($catLabel($item->category), $catTone($item->category)) : '<span class="text-muted">&mdash;</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars((string) ($item->merchant ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item->payment_account)): ?>
                                            <span class="small"><?php echo htmlspecialchars((string) $item->payment_account, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item->client_id)): ?>
                                            <a href="<?php echo Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return={$returnUrl}"); ?>">
                                                <?php echo htmlspecialchars((string) ($item->client_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                            <?php if (!empty($item->account_name) || !empty($item->project_name)): ?>
                                                <div class="small text-muted">
                                                    <?php
                                                    $crumb = array_filter([$item->account_name ?? '', $item->project_name ?? '']);
                                                    echo htmlspecialchars(implode(' › ', $crumb), ENT_QUOTES, 'UTF-8');
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_UNASSIGNED'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo ((int) $item->billable === 1)
                                            ? $msPill(Text::_('JYES'), 'green')
                                            : '<span class="text-muted">&mdash;</span>'; ?>
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                                        $<?php echo number_format((float) $item->amount, 2, '.', ','); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="d-none d-lg-table-cell"></td>
                                <td colspan="8" class="text-end fw-bold"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_PAGE_TOTAL'); ?></td>
                                <td class="text-end fw-bold" style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                                    $<?php echo number_format($pageTotal, 2, '.', ','); ?>
                                </td>
                            </tr>
                        </tfoot>
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
