<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\Ai\AiUsage;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Aiusage\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$returnUrl = base64_encode(Route::_('index.php?option=com_mothership&view=aiusage'));

// Where a call's resource is edited.
$resourceTask = ['invoice' => 'invoice.edit'];

$cards = [
    'this_month' => 'COM_MOTHERSHIP_AIUSAGE_CARD_THIS_MONTH',
    'last_month' => 'COM_MOTHERSHIP_AIUSAGE_CARD_LAST_MONTH',
    'all_time'   => 'COM_MOTHERSHIP_AIUSAGE_CARD_ALL_TIME',
];

$pageTotal = 0.0;
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&view=aiusage'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <div class="row g-3 mb-3" id="aiUsageSummary">
                    <?php foreach ($cards as $key => $label):
                        $total = $this->summary[$key] ?? ['calls' => 0, 'cost' => 0.0];
                    ?>
                        <div class="col-12 col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="small text-muted"><?php echo Text::_($label); ?></div>
                                    <div class="fs-3 fw-bold" style="font-variant-numeric:tabular-nums;">
                                        $<?php echo number_format((float) $total['cost'], 2, '.', ','); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo Text::plural('COM_MOTHERSHIP_AIUSAGE_N_CALLS', (int) $total['calls']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="small text-muted"><?php echo Text::_('COM_MOTHERSHIP_AIUSAGE_ESTIMATE_NOTE'); ?></p>

                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)): ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else: ?>
                    <table class="table itemList" id="aiUsageList">
                        <thead>
                            <tr>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_DATE', 'u.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_FEATURE', 'u.feature', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_MOTHERSHIP_AIUSAGE_HEADING_ABOUT'); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_CLIENT', 'c.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_MODEL', 'u.model', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-end">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_INPUT', 'u.input_tokens', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-end">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_OUTPUT', 'u.output_tokens', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-end">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_MOTHERSHIP_AIUSAGE_HEADING_COST', 'u.cost', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item):
                                $pageTotal += (float) $item->cost;
                                $task = $resourceTask[$item->resource_type ?? ''] ?? null;
                            ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(AiUsage::featureLabel((string) $item->feature), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php if ($task && !empty($item->resource_id)): ?>
                                            <a href="<?php echo Route::_("index.php?option=com_mothership&task={$task}&id={$item->resource_id}&return={$returnUrl}"); ?>">
                                                <?php echo Text::_('COM_MOTHERSHIP_AIUSAGE_RESOURCE_' . strtoupper($item->resource_type)); ?> #<?php echo (int) $item->resource_id; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item->client_id)): ?>
                                            <a href="<?php echo Route::_("index.php?option=com_mothership&task=client.edit&id={$item->client_id}&return={$returnUrl}"); ?>">
                                                <?php echo htmlspecialchars((string) ($item->client_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_UNASSIGNED'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars((string) ($item->model ?? $item->provider), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                        <?php echo number_format((int) $item->input_tokens); ?>
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                        <?php echo number_format((int) $item->output_tokens); ?>
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                                        <?php if ($item->cost !== null): ?>
                                            $<?php echo number_format((float) $item->cost, 4, '.', ','); ?>
                                        <?php else: ?>
                                            <span class="text-muted" title="<?php echo Text::_('COM_MOTHERSHIP_AIUSAGE_NO_RATE'); ?>">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end fw-bold"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_PAGE_TOTAL'); ?></td>
                                <td class="text-end fw-bold" style="font-variant-numeric:tabular-nums;white-space:nowrap;">
                                    $<?php echo number_format($pageTotal, 4, '.', ','); ?>
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
