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

/** @var \TrevorBice\Component\Mothership\Administrator\View\Invoice\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = $this->getCurrentUser();
$userId = $user->id;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<style>
    .account-container {
        overflow: hidden;
        height: 0;
        transition: height 400ms ease;
    }

    .account-container.open {
        height: auto;
    }

    .account-loading-spinner,
    .account_id_wrapper {
        opacity: 0;
        visibility: hidden;
        transition: opacity 300ms ease, visibility 300ms ease;
    }

    .account-loading-spinner.show,
    .account_id_wrapper.show {
        opacity: 1;
        visibility: visible;
    }
</style>


<form action="<?php echo Route::_('index.php?option=com_mothership&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="invoice-form" aria-label="<?php echo Text::_('COM_MOTHERSHIP_INVOICE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_MOTHERSHIP_FORM_INVOICE_DETAILS_TAB')); ?>
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
                        <div class="account_id_wrapper">
                            <?php echo $this->form->renderField('account_id'); ?>
                        </div>
                    </div>
                    <?php echo $this->form->renderField('number'); ?>
                    <?php echo $this->form->renderField('rate'); ?>
                    <?php echo $this->form->renderField('total'); ?>
                    
                                      
                    </fieldset>
                </div>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('status'); ?>
                <?php echo $this->form->renderField('created'); ?>
                <?php echo $this->form->renderField('due_date'); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div>
                    <fieldset class="adminform">
                    <?php echo $this->form->getInput('items'); ?>
                    </fieldset>
                </div>
            </div>
        </div>

        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (isset($this->item->id) && $this->item->id > 0) ? (int) $this->item->id : ""; ?>" />
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const clientField = document.querySelector('#jform_client_id');
    const accountContainer = document.querySelector('.account-container');
    const spinner = document.querySelector('.account-loading-spinner');
    const accountWrapper = document.querySelector('.account_id_wrapper');
    const accountField = document.querySelector('#jform_account_id');
    let previousClientId = clientField.value;
    const MIN_SPINNER_TIME = 500;

    function toggleAccountField() {
        const clientId = clientField.value;

        if (!clientId || clientId === '0') {
            hideAccountField();
            previousClientId = clientId;
            return;
        }

        if (previousClientId !== clientId) {
            if (!accountContainer.classList.contains('open')) {
                openContainer();
            }
            showSpinner();
            fetchAccounts(clientId);
            previousClientId = clientId;
        }
    }

    function fetchAccounts(clientId) {
        const startTime = Date.now();
        fetch(`index.php?option=com_mothership&task=invoice.getAccountsForClient&format=json&client_id=${clientId}`)
            .then(res => res.json())
            .then(data => {
                accountField.innerHTML = '<option value="">Select Account</option>';
                if (data.data) {
                    data.data.forEach(account => {
                        accountField.innerHTML += `<option value="${account.id}">${account.name}</option>`;
                    });
                }
            })
            .catch(console.error)
            .finally(() => {
                const elapsed = Date.now() - startTime;
                setTimeout(() => {
                    hideSpinner();
                    showAccountField();
                }, Math.max(0, MIN_SPINNER_TIME - elapsed));
            });
    }

    function showSpinner() {
        spinner.classList.add('show');
        accountWrapper.classList.remove('show');
    }

    function hideSpinner() {
        spinner.classList.remove('show');
    }

    function showAccountField() {
        accountWrapper.classList.add('show');
    }

    function hideAccountField() {
        accountWrapper.classList.remove('show');
        accountField.innerHTML = '<option value="">Select Account</option>';
        closeContainer();
    }

    function openContainer() {
        accountContainer.classList.add('open');
        accountContainer.style.height = `${accountContainer.scrollHeight}px`;
        setTimeout(() => {
            accountContainer.style.height = 'auto';
        }, 400);
    }

    function closeContainer() {
        accountContainer.style.height = `${accountContainer.scrollHeight}px`;
        requestAnimationFrame(() => {
            accountContainer.style.height = '0';
        });
        accountContainer.addEventListener('transitionend', () => {
            accountContainer.classList.remove('open');
        }, { once: true });
    }

    toggleAccountField();
    clientField.addEventListener('change', toggleAccountField);
});
</script>
