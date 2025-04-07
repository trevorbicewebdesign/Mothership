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
    <p><div class="border rounded p-2 mt-1 bg-light">
        <?= $project->description ?>
    </div></p>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row mb-12">
                <div class="col-md-12">
                    <strong>Account</strong>
                    <?= $project->account_name !== null ? $project->account_name : '<em>None</em>' ?>
                </div>
                <div class="col-md-12">
                    <strong>Status:</strong> <?= ucfirst($project->status) ?>
                </div>
                <div class="col-md-12">
                    <strong>Type:</strong> <?= htmlspecialchars($project->type ?? '-') ?>
                </div>
                <div class="col-md-12">
                    <strong>Created:</strong> <?= $created ?>
                </div>
            </div>
        </div>
    </div>
</div>