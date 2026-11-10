<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Portalusers;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use TrevorBice\Component\Mothership\Administrator\Model\PortalusersModel;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Everyone with client portal access, across all clients.
 */
class HtmlView extends BaseHtmlView
{
    public $filterForm;

    public $activeFilters = [];

    protected $items = [];

    protected $pagination;

    protected $state;

    /** Invites nobody has accepted yet, across all clients. */
    protected $pending = [];

    /** Whether the current user may change anyone's access. */
    protected $canEdit = false;

    public function display($tpl = null): void
    {
        /** @var PortalusersModel $model */
        $model               = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->pending       = ClientAccess::pendingInvites();
        $this->canEdit       = (bool) ContentHelper::getActions('com_mothership')->get('core.edit');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo   = ContentHelper::getActions('com_mothership');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MOTHERSHIP_MANAGER_PORTALUSERS'), 'users mothership-portalusers');

        if ($canDo->get('core.edit')) {
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->standardButton('mail', 'COM_MOTHERSHIP_PORTALUSERS_RESEND', 'portalusers.resend')->listCheck(true);
            // A confirm button: removing access is not undoable from here.
            $childBar->delete('portalusers.remove', 'COM_MOTHERSHIP_PORTALUSERS_REMOVE')
                ->message('COM_MOTHERSHIP_PORTALUSERS_REMOVE_CONFIRM')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_mothership');
        }
    }
}
