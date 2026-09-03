<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoices
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @var  array                          $invoices  Open invoice rows.
 * @var  object                         $summary   {count, outstanding}.
 * @var  \Joomla\Registry\Registry      $params    Module params.
 * @var  object                         $module    The module record.
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$listUrl = Route::_('index.php?option=com_mothership&view=invoices');

$money = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};
?>
<div class="mod-mothership-invoices" style="padding: 4px 14px 14px;">
	<?php if (empty($invoices)) : ?>
		<div class="text-center text-muted p-3">
			<span class="icon-checkmark-circle text-success" aria-hidden="true"></span>
			<div class="mt-2"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES_NONE'); ?></div>
		</div>
	<?php else : ?>
		<?php if ($params->get('show_summary', 1)) : ?>
			<div class="d-flex justify-content-between align-items-center mb-2">
				<span class="badge bg-warning text-dark">
					<?php echo Text::plural('MOD_MOTHERSHIP_INVOICES_N_OPEN', (int) $summary->count); ?>
				</span>
				<span class="fw-bold">
					<?php echo Text::sprintf('MOD_MOTHERSHIP_INVOICES_OUTSTANDING', $money($summary->outstanding)); ?>
				</span>
			</div>
		<?php endif; ?>

		<div class="table-responsive">
			<table class="table table-sm mb-0">
				<caption class="visually-hidden"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES'); ?></caption>
				<thead>
					<tr>
						<th scope="col"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES_HEADING_INVOICE'); ?></th>
						<th scope="col"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES_HEADING_CLIENT'); ?></th>
						<th scope="col" class="text-end"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES_HEADING_BALANCE'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($invoices as $invoice) : ?>
						<?php
						$editUrl = Route::_('index.php?option=com_mothership&task=invoice.edit&id=' . (int) $invoice->id);
						$who     = $invoice->account_name ?: ($invoice->client_name ?: Text::_('MOD_MOTHERSHIP_INVOICES_UNASSIGNED'));
						$partial = (float) $invoice->total_paid > 0;
						?>
						<tr>
							<td>
								<a href="<?php echo $editUrl; ?>">
									#<?php echo htmlspecialchars($invoice->number ?? $invoice->id, ENT_QUOTES, 'UTF-8'); ?>
								</a>
								<?php if ($partial) : ?>
									<span class="badge bg-info text-dark ms-1"><?php echo Text::_('MOD_MOTHERSHIP_INVOICES_PARTIAL'); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo htmlspecialchars($who, ENT_QUOTES, 'UTF-8'); ?></td>
							<td class="text-end fw-bold"><?php echo $money($invoice->balance); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="text-end mt-2">
			<a href="<?php echo $listUrl; ?>" class="btn btn-sm btn-outline-primary">
				<?php echo Text::_('MOD_MOTHERSHIP_INVOICES_VIEW_ALL'); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
