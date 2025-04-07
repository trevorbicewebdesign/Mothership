<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$project = $this->item;

// Format date fields
$created = $project->created ? HTMLHelper::_('date', $project->created, Text::_('DATE_FORMAT_LC4')) : '-';
?>

<div class="container my-4">
    <h1><?= $project->name ?></h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Client ID:</strong> <?= (int) $project->client_id ?>
                </div>
                <div class="col-md-6">
                    <strong>Account ID:</strong>
                    <?= $project->account_id !== null ? (int) $project->account_id : '<em>None</em>' ?>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Status:</strong> <?= ucfirst($project->status) ?>
                </div>
                <div class="col-md-4">
                    <strong>Type:</strong> <?= htmlspecialchars($project->type ?? '-') ?>
                </div>
                <div class="col-md-4">
                    <strong>Created:</strong> <?= $created ?>
                </div>
            </div>

            <?php if (!empty($project->description)): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Description:</strong>
                        <div class="border rounded p-2 mt-1 bg-light">
                            <?= nl2br(htmlspecialchars($project->description)) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>