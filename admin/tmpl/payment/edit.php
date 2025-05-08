<?php
/**
 * Payment Edit Template for Mothership Component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

JHtml::_('behavior.formvalidator');
// JHtml::_('formbehavior.chosen', 'select');

/** @var \TrevorBice\Component\Mothership\Administrator\View\Payment\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = $this->getCurrentUser();
?>

<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post"
      name="adminForm"
      id="payment-form"
      aria-label="<?php echo Text::_('COM_MOTHERSHIP_PAYMENT_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>"
      class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'paymentTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'paymentTab', 'details', Text::_('COM_MOTHERSHIP_FORM_PAYMENT_DETAILS_TAB')); ?>
        <div class="row">
            <div class="col-lg-8">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('client_id'); ?>
                    <div class="account-container">
                        <div class="account-loading-spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <?php echo Text::_('Loading accounts...'); ?>
                        </div>
                        <div class="account_id_wrapper" style="opacity: 1;">
                            <?php echo $this->form->renderField('account_id'); ?>
                        </div>
                    </div>
                    <?php echo $this->form->renderField('amount'); ?>
                    <?php echo $this->form->renderField('payment_date'); ?>
                    <?php echo $this->form->renderField('fee_amount'); ?>
                    <?php echo $this->form->renderField('fee_passed_on'); ?>
                    <?php echo $this->form->renderField('payment_method'); ?>
                    <?php echo $this->form->renderField('transaction_id'); ?>
                </fieldset>
            </div>
            <div class="col-lg-4">
                <fieldset class="adminform">
                    <?php echo $this->form->renderField('status'); ?>
                    <?php echo $this->form->renderField('processed_date'); ?>
                    <?php echo $this->form->renderField('created_at'); ?>
                    <?php echo $this->form->renderField('updated_at'); ?>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (isset($this->item->id) && $this->item->id > 0) ? (int) $this->item->id : ""; ?>" />
    <input type="hidden" name="jform[return]" value="<?php echo isset($_REQUEST['return'])?$_REQUEST['return']:''; ?>" />
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
