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
			<div class="row mb-3">
				<div class="col-md-6">
					<strong>Client ID:</strong> <?= (int) $domain->client_id ?>
				</div>
				<div class="col-md-6">
					<strong>Account ID:</strong> <?= $domain->account_id !== null ? (int) $domain->account_id : '<em>None</em>' ?>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-4">
					<strong>Status:</strong> <?= (int) $domain->status ?>
				</div>
				<div class="col-md-4">
					<strong>Auto Renew:</strong> <?= $domain->auto_renew ? 'Yes' : 'No' ?>
				</div>
				<div class="col-md-4">
					<strong>Registrar:</strong> <?= htmlspecialchars($domain->registrar ?? '-') ?>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-6">
					<strong>Reseller:</strong> <?= htmlspecialchars($domain->reseller ?? '-') ?>
				</div>
				<div class="col-md-6">
					<strong>DNS Provider:</strong> <?= htmlspecialchars($domain->dns_provider ?? '-') ?>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-md-3"><strong>NS1:</strong> <?= htmlspecialchars($domain->ns1 ?? '-') ?></div>
				<div class="col-md-3"><strong>NS2:</strong> <?= htmlspecialchars($domain->ns2 ?? '-') ?></div>
				<div class="col-md-3"><strong>NS3:</strong> <?= htmlspecialchars($domain->ns3 ?? '-') ?></div>
				<div class="col-md-3"><strong>NS4:</strong> <?= htmlspecialchars($domain->ns4 ?? '-') ?></div>
			</div>

			<div class="row mb-3">
				<div class="col-md-6">
					<strong>Purchase Date:</strong> <?= $purchaseDate ?>
				</div>
				<div class="col-md-6">
					<strong>Expiration Date:</strong> <?= $expirationDate ?>
				</div>
			</div>

			<?php if (!empty($domain->notes)) : ?>
				<div class="row mb-3">
					<div class="col-12">
						<strong>Notes:</strong>
						<div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars($domain->notes)) ?></div>
					</div>
				</div>
			<?php endif; ?>

			<div class="text-muted small">
				Created: <?= HTMLHelper::_('date', $domain->created, Text::_('DATE_FORMAT_LC4')) ?> |
				Modified: <?= HTMLHelper::_('date', $domain->modified, Text::_('DATE_FORMAT_LC4')) ?>
			</div>
		</div>
	</div>

	<div class="mt-4">
		<a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_mothership&view=domains') ?>">← Back to Domains</a>
	</div>
</div>
