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
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
