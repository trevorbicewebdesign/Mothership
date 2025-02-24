<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Clients;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\ClientsModel;

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

    public function display($tpl = null): void
    {
        /** @var ClientsModel $model */
        $model = $this->getModel();
        $this->items = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();
        $this->filterForm = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

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

        ToolbarHelper::title(Text::_('COM_MOTHERSHIP_MANAGER_CLIENTS'), 'bookmark mothership-clients');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('client.add');
        }

        if (!$this->isEmptyState && ($canDo->get('core.edit.state') || $canDo->get('core.admin'))) {
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('clients.publish')->listCheck(true);
            $childBar->unpublish('clients.unpublish')->listCheck(true);
            $childBar->archive('clients.archive')->listCheck(true);

            if ($canDo->get('core.admin')) {
                $childBar->checkin('clients.checkin')->listCheck(true);
            }

            if (!$this->state->get('filter.state') == -2) {
                $childBar->trash('clients.trash')->listCheck(true);
            }
        }

        if (!$this->isEmptyState && $this->state->get('filter.state') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('clients.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_mothership');
        }

        $toolbar->help('Mothership:_Clients');
    }
}
