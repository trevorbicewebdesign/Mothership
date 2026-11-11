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

/** @var \TrevorBice\Component\Mothership\Administrator\View\Expenseimport\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_mothership&task=expense.importReceipt'); ?>" method="post" enctype="multipart/form-data" id="expense-import-form" class="form-validate">
    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <p><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_INTRO'); ?></p>
            <div class="mb-3">
                <label for="expense-import-file" class="form-label"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_FILE_LABEL'); ?></label>
                <input type="file" name="receipt[]" id="expense-import-file" class="form-control" multiple accept=".pdf,.png,.jpg,.jpeg,.webp,application/pdf,image/png,image/jpeg,image/webp" required>
                <div class="form-text"><?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_FILE_HINT'); ?></div>
            </div>
            <button type="submit" class="btn btn-primary" id="expense-import-submit">
                <span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_SUBMIT'); ?>
            </button>
            <span class="ms-2 text-muted d-none" id="expense-import-busy">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_BUSY'); ?>
            </span>
            <p class="small text-muted mt-3 mb-0" id="expense-import-notice">
                <?php echo $this->aiEnabled
                    ? Text::sprintf('COM_MOTHERSHIP_EXPENSE_IMPORT_AI_NOTICE', $this->escape($this->provider))
                    : Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_AI_OFF'); ?>
                <?php echo Text::_('COM_MOTHERSHIP_EXPENSE_IMPORT_NOTHING_SAVED'); ?>
            </p>
        </div>
    </div>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
document.getElementById('expense-import-form').addEventListener('submit', function () {
    document.getElementById('expense-import-submit').disabled = true;
    document.getElementById('expense-import-busy').classList.remove('d-none');
});
</script>
