<?php
/**
 * Expense Edit Template for Mothership Component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');

/** @var \TrevorBice\Component\Mothership\Administrator\View\Expense\HtmlView $this */
?>
<style>
 .account-loading-spinner,
 .project-loading-spinner {
    display: none;
 }
</style>
<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post"
      name="adminForm"
      id="expense-form"
      aria-label="<?php echo Text::_('COM_MOTHERSHIP_EXPENSE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>"
      class="form-validate">
    <?php if ($this->importQueue) : ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center" id="expense-import-queue">
            <span>
                <?php echo Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_QUEUE', $this->importQueue['current'], $this->importQueue['total']); ?>
                <?php echo Text::_($this->importQueue['left'] ? 'COM_MOTHERSHIP_EXPENSE_IMPORT_QUEUE_NEXT' : 'COM_MOTHERSHIP_EXPENSE_IMPORT_QUEUE_LAST'); ?>
            </span>
            <?php if ($this->importQueue['left']) : ?>
                <a class="btn btn-sm btn-outline-secondary" id="expense-import-stop" href="<?php echo Route::_('index.php?option=com_mothership&task=expense.importStop&' . Session::getFormToken() . '=1'); ?>">
                    <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_STOP'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'expenseTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'expenseTab', 'details', Text::_('COM_MOTHERSHIP_FORM_EXPENSE_DETAILS_TAB')); ?>
        <div class="row">
            <div class="col-lg-8">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('item'); ?>
                    <?php echo $this->form->renderField('merchant'); ?>
                    <?php echo $this->form->renderField('category'); ?>
                    <?php echo $this->form->renderField('amount'); ?>
                    <?php echo $this->form->renderField('expense_date'); ?>
                    <?php echo $this->form->renderField('transaction_id'); ?>
                    <?php echo $this->form->renderField('payment_account'); ?>
                    <?php echo $this->form->renderField('description'); ?>
                </fieldset>
            </div>
            <div class="col-lg-4">
                <?php foreach ($this->receipts as $receipt) : ?>
                    <?php $url = Route::_($receipt['url']); ?>
                    <div class="card mb-3 expense-receipt">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><span class="icon-file" aria-hidden="true"></span> <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_RECEIPT'); ?></span>
                            <a href="<?php echo $url; ?>" target="_blank" rel="noopener" class="small expense-receipt-open"><?php echo $this->escape($receipt['name']); ?></a>
                        </div>
                        <?php if ($receipt['mime'] === 'application/pdf') : ?>
                            <iframe src="<?php echo $url; ?>#view=FitH" title="<?php echo $this->escape($receipt['name']); ?>" style="width: 100%; height: 560px; border: 0;"></iframe>
                        <?php else : ?>
                            <a href="<?php echo $url; ?>" target="_blank" rel="noopener"><img src="<?php echo $url; ?>" alt="<?php echo $this->escape($receipt['name']); ?>" style="max-width: 100%;"></a>
                        <?php endif; ?>
                        <?php if ($receipt['pending']) : ?>
                            <div class="card-footer small text-muted"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_RECEIPT_PENDING'); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('created_at'); ?>
                    <?php echo $this->form->renderField('updated_at'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'expenseTab', 'assignment', Text::_('COM_MOTHERSHIP_FORM_EXPENSE_ASSIGNMENT_TAB')); ?>
        <div class="row">
            <div class="col-lg-8">
                <p class="text-muted">
                    <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_ASSIGNMENT_HINT'); ?>
                </p>
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('client_id'); ?>
                    <div class="account-container">
                        <div class="account-loading-spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOTHERSHIP_LOADING_ACCOUNTS'); ?>
                        </div>
                        <div class="account_id_wrapper">
                            <?php echo $this->form->renderField('account_id'); ?>
                        </div>
                    </div>
                    <div class="project-container">
                        <div class="project-loading-spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <?php echo Text::_('COM_MOTHERSHIP_LOADING_PROJECTS'); ?>
                        </div>
                        <div class="project_id_wrapper">
                            <?php echo $this->form->renderField('project_id'); ?>
                        </div>
                    </div>
                    <?php echo $this->form->renderField('billable'); ?>
                    <?php echo $this->form->renderField('reimbursed'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (isset($this->item->id) && $this->item->id > 0) ? (int) $this->item->id : ''; ?>" />
    <input type="hidden" name="jform[return]" value="<?php echo isset($_REQUEST['return']) ? htmlspecialchars($_REQUEST['return'], ENT_QUOTES) : ''; ?>" />
    <?php echo $this->form->renderField('receipt_import'); ?>
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
