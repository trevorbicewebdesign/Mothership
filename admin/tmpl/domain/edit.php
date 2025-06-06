<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

JHtml::_('behavior.formvalidator');
// JHtml::_('formbehavior.chosen', 'select');

/** @var \TrevorBice\Component\Mothership\Administrator\View\Domain\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = $this->getCurrentUser();
$userId = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<style>
 .account-loading-spinner,
 .project-loading-spinner {
    display:none;
 }
</style>
<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="domain-form" aria-label="<?php echo Text::_('COM_MOTHERSHIP_DOMAIN_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_MOTHERSHIP_FORM_DOMAIN_DETAILS_TAB')); ?>
        <div class="row">
            <div class="col-lg-9">
                <div>
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

                        <?php echo $this->form->renderField('name'); ?>
                        <?php echo $this->form->renderField('status'); ?>
                        <?php echo $this->form->renderField('epp_status'); ?>
                        <?php echo $this->form->renderField('registrar'); ?>
                        <?php echo $this->form->renderField('reseller'); ?>
                        <?php echo $this->form->renderField('dns_provider'); ?>
                        <?php echo $this->form->renderField('notes'); ?>
                    </fieldset>
                </div>
            </div>
            <div class="col-lg-3">  
                <?php echo $this->form->renderField('purchase_date'); // The date the domain was purchased ?>            
                <?php echo $this->form->renderField('created'); // The date the record was created ?>
                <?php echo $this->form->renderField('expiration_date'); // The expiration date of the domain ?>
                <?php echo $this->form->renderField('updated'); // The date the domain was last scanned ?>
                <?php echo $this->form->renderFIeld("ns1"); ?>
                <?php echo $this->form->renderFIeld("ns2"); ?>
                <?php echo $this->form->renderFIeld("ns3"); ?>
                <?php echo $this->form->renderFIeld("ns4"); ?>
                
            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (isset($this->item->id) && $this->item->id > 0) ? (int) $this->item->id : ""; ?>" />
    <input type="hidden" name="jform[return]" value="<?php echo isset($_REQUEST['return'])?$_REQUEST['return']:''; ?>" />
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>