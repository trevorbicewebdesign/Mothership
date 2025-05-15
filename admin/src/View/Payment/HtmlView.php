<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Payment;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\PaymentModel;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit an payment.
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var    Form
     * @since  1.5
     */
    protected $form;

    /**
     * The active item
     *
     * @var    \stdClass
     * @since  1.5
     */
    protected $item;

    /**
     * The model state
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.5
     */
    protected $state;

    /**
     * Object containing permissions for the item
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.5
     */
    protected $canDo;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since   1.5
     *
     * @throws  \Exception
     */
    public function display($tpl = null): void
    {
        /** @var PaymentModel $model */
        $model = $this->getModel();
        $this->item = $model->getItem();
        if ($this->item === false) {
            // Redirect to the list view if no item is found
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PAYMENT_NOT_FOUND'), 'error');
            $app->redirect(Factory::getApplication()->input->get('return', 'index.php?option=com_mothership&view=payments', 'raw'));    
        }
        $this->form = $model->getForm();
        $this->state = $model->getState();
        $this->helper = new MothershipHelper;
        $this->canDo = ContentHelper::getActions('com_mothership');

        $isLocked = $this->item->locked;
        if ($isLocked && $this->form instanceof Form)
        {
            foreach ($this->form->getFieldsets() as $fieldset)
            {
                if (isset($fieldset->name)) {
                    foreach ($this->form->getFieldset($fieldset->name) as $field)
                    {
                        $this->form->setFieldAttribute($field->fieldname, 'readonly', 'readonly');
                        $this->form->setFieldAttribute($field->fieldname, 'disabled', 'disabled');                        
                    }
                }
            }
        }

        $wa = $this->getDocument()->getWebAssetManager();
        $jsPath = JPATH_ROOT . '/administrator/components/com_mothership/assets/js/payment-edit.js';
        $jsVersion = filemtime($jsPath);
        $wa->useScript('jquery');
        $wa->registerAndUseScript('com_mothership.payment-edit', 'administrator/components/com_mothership/assets/js/payment-edit.js', [], ['defer' => true, 'version' => $jsVersion]);
        $wa->registerAndUseStyle('com_mothership.payment-edit', 'media/com_mothership/css/payment-edit.css');

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.6
     *
     * @throws  \Exception
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user = $this->getCurrentUser();
        $isNew = empty($this->item->id);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $user->id);
        $canDo = $this->canDo;
        $isLocked = $this->item->locked;
        $toolbar = $this->getDocument()->getToolbar();


        ToolbarHelper::title(
            $isLocked 
            ? Text::_('COM_MOTHERSHIP_MANAGER_PAYMENT_VIEW') 
            : ($isNew 
                ? Text::_('COM_MOTHERSHIP_MANAGER_PAYMENT_NEW') 
                : Text::_('COM_MOTHERSHIP_MANAGER_PAYMENT_EDIT')),
            'bookmark mothership-payments'
        );

        // If not checked out, can save the item.
        if (!$checkedOut && !$isLocked && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('payment.apply');
        }

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(
            function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew, $isLocked) {
                // If not checked out, can save the item.
                if (!$checkedOut && !$isLocked && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
                    $childBar->save('payment.save');
                }

                if (!$checkedOut && !$isLocked && $canDo->get('core.create')) {
                    $childBar->save2new('payment.save2new');
                }

                // If an existing item, can save to a copy.
                if (!$isNew && !$isLocked && $canDo->get('core.create')) {
                    $childBar->save2copy('payment.save2copy');
                }
            }
        );

        if($isLocked){
            ToolbarHelper::custom('payment.unlock', 'unlock', 'unlock', 'COM_MOTHERSHIP_UNLOCK', false);
        } else {
            ToolbarHelper::custom('payment.lock', 'lock', 'lock', 'COM_MOTHERSHIP_LOCK', false);
        }

        // Make a Confirm Payment button below
        if ($this->item->status == '1' && $canDo->get('core.edit')) {
            ToolbarHelper::custom('payment.confirm', 'vcard', 'vcard', 'COM_MOTHERSHIP_CONFIRM_PAYMENT', false);
        }


        if (empty($this->item->id)) {
            $toolbar->cancel('payment.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('payment.cancel');

            if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit')) {
                $toolbar->versions('com_mothership.payment', $this->item->id);
            }
        }
    }
}
