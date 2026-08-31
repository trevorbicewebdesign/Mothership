<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \TrevorBice\Component\Mothership\Administrator\View\Ticket\HtmlView $this */

HTMLHelper::_('behavior.formvalidator');

$isNew = (int) $this->item->id === 0;
?>
<style>
 .account-loading-spinner,
 .project-loading-spinner {
    display: none;
 }
</style>
<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="ticket-form" aria-label="<?php echo Text::_('COM_MOTHERSHIP_MANAGER_TICKET_' . ($isNew ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'ticketTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'ticketTab', 'details', Text::_('COM_MOTHERSHIP_FORM_TICKET_DETAILS_TAB')); ?>
        <div class="row">
            <div class="col-lg-9">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('subject'); ?>
                    <?php echo $this->form->renderField('client_id'); ?>
                    <div class="account-container">
                        <div class="account-loading-spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOTHERSHIP_LOADING_ACCOUNTS'); ?>
                        </div>
                        <div class="account_id_wrapper" style="opacity: 1;">
                            <?php echo $this->form->renderField('account_id'); ?>
                        </div>
                    </div>
                    <div class="project-container">
                        <div class="project-loading-spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOTHERSHIP_LOADING_PROJECTS'); ?>
                        </div>
                        <div class="project_id_wrapper" style="opacity: 1;">
                            <?php echo $this->form->renderField('project_id'); ?>
                        </div>
                    </div>
                    <?php echo $this->form->renderField('type'); ?>
                    <?php echo $this->form->renderField('reference_url'); ?>
                    <?php echo $this->form->renderField('description'); ?>
                </fieldset>
            </div>
            <div class="col-lg-3">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('status'); ?>
                    <?php echo $this->form->renderField('priority'); ?>
                    <?php echo $this->form->renderField('assigned_to'); ?>
                </fieldset>
                <?php if (!$isNew) : ?>
                    <p class="mt-2">
                        <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"
                           href="<?php echo Uri::root() . 'index.php?option=com_mothership&view=ticket&id=' . (int) $this->item->id; ?>">
                            <span class="icon-out-2" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOTHERSHIP_TICKET_VIEW_IN_PORTAL'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'ticketTab', 'billing', Text::_('COM_MOTHERSHIP_FIELDSET_TICKET_BILLING')); ?>
        <div class="row">
            <div class="col-lg-6">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('billable'); ?>
                    <?php echo $this->form->renderField('estimated_minutes'); ?>
                    <?php echo $this->form->renderField('logged_minutes'); ?>
                    <?php echo $this->form->renderField('rate'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if (!$isNew) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'ticketTab', 'conversation', Text::sprintf('COM_MOTHERSHIP_FORM_TICKET_CONVERSATION_TAB', count($this->comments))); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php if (empty($this->comments)) : ?>
                    <div class="alert alert-info"><?php echo Text::_('COM_MOTHERSHIP_TICKET_NO_REPLIES'); ?></div>
                <?php else : ?>
                    <?php foreach ($this->comments as $c) : ?>
                        <div class="card mb-2 <?php echo $c->is_internal ? 'border-warning' : ''; ?>">
                            <div class="card-body py-2 px-3">
                                <div class="small text-muted mb-1">
                                    <strong><?php echo htmlspecialchars((string) ($c->author_name ?? 'User')); ?></strong>
                                    &middot; <?php echo HTMLHelper::_('date', $c->created, Text::_('DATE_FORMAT_LC2')); ?>
                                    <?php if ($c->is_internal) : ?> &middot; <em><?php echo Text::_('COM_MOTHERSHIP_TICKET_INTERNAL_NOTE'); ?></em><?php endif; ?>
                                </div>
                                <div><?php echo nl2br(htmlspecialchars((string) $c->body)); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p class="text-muted small mt-2"><?php echo Text::_('COM_MOTHERSHIP_TICKET_REPLY_HINT'); ?></p>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
