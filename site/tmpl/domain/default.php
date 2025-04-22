<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$domain = $this->item;

// Format dates
$purchaseDate = $domain->purchase_date ? HTMLHelper::_('date', $domain->purchase_date, Text::_('DATE_FORMAT_LC4')) : '-';
$expirationDate = $domain->expiration_date ? HTMLHelper::_('date', $domain->expiration_date, Text::_('DATE_FORMAT_LC4')) : '-';
?>

<div class="container my-4">
	<h1 class="mb-4">Domain: <?= htmlspecialchars($domain->name) ?></h1>

	<div class="card shadow-sm">
		<div class="card-body">
			<!-- Domain Metadata -->
			<dl class="row">

				<dt class="col-md-3">Account Name:</dt>
				<dd class="col-md-3"><?= $domain->account_name !== null ? (int) $domain->account_name : '<em>None</em>' ?></dd>

				<dt class="col-md-3">Status:</dt>
				<dd class="col-md-3"><?= ucfirst(htmlspecialchars($domain->status)) ?></dd>

				<dt class="col-md-3">Auto Renew:</dt>
				<dd class="col-md-3"><?= $domain->auto_renew ? 'Yes' : 'No' ?></dd>

				<dt class="col-md-3">Registrar:</dt>
				<dd class="col-md-3"><?= htmlspecialchars($domain->registrar ?? '-') ?></dd>

				<dt class="col-md-3">Reseller:</dt>
				<dd class="col-md-3"><?= htmlspecialchars($domain->reseller ?? '-') ?></dd>

				<dt class="col-md-3">DNS Provider:</dt>
				<dd class="col-md-3"><?= htmlspecialchars($domain->dns_provider ?? '-') ?></dd>
			</dl>

			<!-- Nameservers -->
			<hr>
			<h5 class="mb-3">Nameservers</h5>
			<dl class="row">
				<?php for ($i = 1; $i <= 4; $i++) : ?>
					<?php $ns = $domain->{'ns' . $i} ?? '-'; ?>
					<dt class="col-md-3">NS<?= $i ?>:</dt>
					<dd class="col-md-3"><?= htmlspecialchars($ns) ?></dd>
				<?php endfor; ?>
			</dl>

			<!-- Dates -->
			<hr>
			<dl class="row">
				<dt class="col-md-3">Purchase Date:</dt>
				<dd class="col-md-3"><?= $purchaseDate ?></dd>

				<dt class="col-md-3">Expiration Date:</dt>
				<dd class="col-md-3"><?= $expirationDate ?></dd>
			</dl>

			<!-- Notes -->
			<?php if (!empty($domain->notes)) : ?>
				<hr>
				<h5 class="mb-2">Notes</h5>
				<div class="bg-light border rounded p-3">
					<?= nl2br(htmlspecialchars($domain->notes)) ?>
				</div>
			<?php endif; ?>

			<!-- Metadata -->
			<hr>
			<div class="text-muted small">
				<i class="icon-calendar"></i> Created: <?= HTMLHelper::_('date', $domain->created, Text::_('DATE_FORMAT_LC4')) ?>
				&nbsp;|&nbsp;
				<i class="icon-pencil"></i> Modified: <?= HTMLHelper::_('date', $domain->modified, Text::_('DATE_FORMAT_LC4')) ?>
			</div>
		</div>
	</div>

	<!-- Back button -->
	<div class="mt-4">
		<a class="btn btn-outline-secondary" href="<?= Route::_('index.php?option=com_mothership&view=domains') ?>">
			← Back to Domains
		</a>
	</div>
</div>
