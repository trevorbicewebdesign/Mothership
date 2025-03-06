<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Invoices;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\InvoicesModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class HtmlView extends BaseHtmlView
{
    public $filterForm;

    public $activeFilters = [];

    protected $items = [];

    protected $pagination;

    protected $state;

    private $isEmptyState = false;

    protected $user;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        // Ensure $user is always a valid object
        if (!$user instanceof User) {
            $user = new User();
        }

        $this->user = $user; // Store the user object for the template
        
        HTMLHelper::_('bootstrap.tooltip');
		HTMLHelper::_('behavior.multiselect');
		HTMLHelper::_('formbehavior.chosen', 'select');

        /** @var InvoicesModel $model */
        $model = $this->getModel();
        $this->items = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();
        $this->filterForm = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        // Ensure transitions is always an array
        $this->transitions = $this->state->get('transitions', []);
        if (!is_array($this->transitions)) {
            $this->transitions = [];
        }

        if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo = ContentHelper::getActions('com_mothership');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MOTHERSHIP_MANAGER_ACCOUNTS'), 'bookmark mothership-invoices');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('invoice.add');
        }

        if (!$this->isEmptyState && ($canDo->get('core.edit.state') || $canDo->get('core.admin'))) {
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('core.admin')) {
                $childBar->checkin('invoices.checkin')->listCheck(true);
            }

            $childBar->delete('invoices.delete')->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_mothership');
        }

        $toolbar->help('Mothership:_Account');
    }
}
