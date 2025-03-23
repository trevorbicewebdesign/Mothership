<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Invoice;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\InvoiceModel;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit an invoice.
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
        /** @var InvoiceModel $model */
        $model = $this->getModel();
        $this->form = $model->getForm();
        $this->item = $model->getItem();
        $this->state = $model->getState();
        $this->helper = new MothershipHelper;
        $this->canDo = ContentHelper::getActions('com_mothership');

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // ✅ Use WebAssetManager to load the script
        $wa = $this->getDocument()->getWebAssetManager();

        // C:\Users\trevo\LocalSites\joomlav4trevorbicecom\app\public\administrator/components/com_mothership/assets/js/invoice-edit.js
        $jsPath = JPATH_COMPONENT . '/assets/js/invoice-edit.js';
        $jsVersion = filemtime($jsPath);
        $wa->useScript('jquery');
        $wa->registerAndUseScript('com_mothership.invoice-edit', 'administrator/components/com_mothership/assets/js/invoice-edit.js', [], ['defer' => true, 'version' => $jsVersion]);
        
        $wa->registerAndUseStyle('com_mothership.invoice-edit', 'administrator/components/com_mothership/assets/css/invoice-edit.css');

        $wa->registerAndUseScript(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            ['defer' => true]
        );
    
        $wa->registerAndUseScript(
            'com_mothership.invoice_items',
            'media/com_mothership/js/invoice-items.js',
            [],
            ['defer' => true]
        );
    
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
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(
            $isNew ? Text::_('COM_MOTHERSHIP_MANAGER_INVOICE_NEW') : Text::_('COM_MOTHERSHIP_MANAGER_INVOICE_EDIT'),
            'bookmark mothership-invoices'
        );

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('invoice.apply');
        }

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(
            function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew) {
                // If not checked out, can save the item.
                if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
                    $childBar->save('invoice.save');
                }

                if (!$checkedOut && $canDo->get('core.create')) {
                    $childBar->save2new('invoice.save2new');
                }

                // If an existing item, can save to a copy.
                if (!$isNew && $canDo->get('core.create')) {
                    $childBar->save2copy('invoice.save2copy');
                }
            }
        );

        if (empty($this->item->id)) {
            $toolbar->cancel('invoice.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('invoice.cancel');

            if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit')) {
                $toolbar->versions('com_mothership.invoice', $this->item->id);
            }
        }
    }
}
