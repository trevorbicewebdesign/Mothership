<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Expense;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\ExpenseModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit an expense.
 *
 * @since  0.0.29
 */
class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $state;

    protected $canDo;

    public function display($tpl = null): void
    {
        /** @var ExpenseModel $model */
        $model      = $this->getModel();
        $this->item = $model->getItem();

        if ($this->item === false) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_ERROR_EXPENSE_NOT_FOUND'), 'error');
            $app->redirect($app->getInput()->get('return', 'index.php?option=com_mothership&view=expenses', 'raw'));
        }

        $this->form  = $model->getForm();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions('com_mothership');

        $wa = $this->getDocument()->getWebAssetManager();
        $jsPath = JPATH_ROOT . '/administrator/components/com_mothership/assets/js/expense-edit.js';
        $wa->useScript('jquery');
        $wa->registerAndUseScript(
            'com_mothership.expense-edit',
            'administrator/components/com_mothership/assets/js/expense-edit.js',
            [],
            ['defer' => true, 'version' => is_file($jsPath) ? filemtime($jsPath) : 'auto']
        );

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $user       = $this->getCurrentUser();
        $isNew      = empty($this->item->id);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $user->id);
        $canDo      = $this->canDo;
        $toolbar    = $this->getDocument()->getToolbar();

        ToolbarHelper::title(
            $isNew
                ? Text::_('COM_MOTHERSHIP_MANAGER_EXPENSE_NEW')
                : Text::_('COM_MOTHERSHIP_MANAGER_EXPENSE_EDIT'),
            'coins mothership-expenses'
        );

        if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
            $toolbar->apply('expense.apply');
        }

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveGroup->configure(
            function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew) {
                if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create'))) {
                    $childBar->save('expense.save');
                }

                if (!$checkedOut && $canDo->get('core.create')) {
                    $childBar->save2new('expense.save2new');
                }

                if (!$isNew && $canDo->get('core.create')) {
                    $childBar->save2copy('expense.save2copy');
                }
            }
        );

        if (empty($this->item->id)) {
            $toolbar->cancel('expense.cancel', 'JTOOLBAR_CANCEL');
        } else {
            $toolbar->cancel('expense.cancel');

            if (ComponentHelper::isEnabled('com_contenthistory') && $this->state->params->get('save_history', 0) && $canDo->get('core.edit')) {
                $toolbar->versions('com_mothership.expense', $this->item->id);
            }
        }
    }
}
