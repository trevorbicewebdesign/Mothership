<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Project;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\ProjectModel;
use TrevorBice\Component\Mothership\Administrator\Helper\MothershipHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit an project.
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
        /** @var ProjectModel $model */
        $model = $this->getModel();
        $this->item = $model->getItem();
        if ($this->item === false) {
            // Redirect to the list view if no item is found
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_PROJECT_NOT_FOUND'), 'error');
            $app->redirect(Factory::getApplication()->input->get('return', 'index.php?option=com_mothership&view=projects', 'raw'));    
        }
        $this->form = $model->getForm();
        $this->state = $model->getState();
        $this->helper = new MothershipHelper;
        $this->canDo = ContentHelper::getActions('com_mothership');

        $wa = $this->getDocument()->getWebAssetManager();
        $jsPath = JPATH_ROOT . '/administrator/components/com_mothership/assets/js/project-edit.js';
        $jsVersion = filemtime($jsPath);
        $wa->useScript('jquery');
        $wa->registerAndUseScript('com_mothership.invoice-edit', 'administrator/components/com_mothership/assets/js/project-edit.js', [], ['defer' => true, 'version' => $jsVersion]);

        $wa->registerAndUseStyle('com_mothership.project-edit', 'media/com_mothership/css/project-edit.css');

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
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(
            $isNew ? Text::_('COM_MOTHERSHIP_MANAGER_PROJECT_NEW') : Text::_('COM_MOTHERSHIP_MANAGER_PROJECT_EDIT'),
            'bookmark mothership-projects'
        );

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('project.apply');
        }

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(
            function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew) {
                // If not checked out, can save the item.
                if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
                    $childBar->save('project.save');
                }

                if (!$checkedOut && $canDo->get('core.create')) {
                    $childBar->save2new('project.save2new');
                }

                // If an existing item, can save to a copy.
                if (!$isNew && $canDo->get('core.create')) {
                    $childBar->save2copy('project.save2copy');
                }
            }
        );

        if(!$isNew)
        {
            ToolbarHelper::custom('project.mothershipScan', 'refresh', '', 'COM_MOTHERSHIP_PROJECT_SCAN_UPDATE', false);
        }

        if (empty($this->item->id)) {
            $toolbar->cancel('project.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('project.cancel');

            if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit')) {
                $toolbar->versions('com_mothership.project', $this->item->id);
            }
        }
    }
}
