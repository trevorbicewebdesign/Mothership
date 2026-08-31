<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Ticket;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View to edit a ticket.
 */
class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $state;

    protected $canDo;

    /** @var array Comment thread for context (read-only). */
    protected $comments = [];

    public function display($tpl = null): void
    {
        $model       = $this->getModel();
        $this->item  = $model->getItem();
        $this->form  = $model->getForm();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions('com_mothership');

        if (!empty($this->item->id)) {
            $this->comments = $model->getComments($this->item->id);
        }

        // Client → Account → Project drill-down (same cascade as Invoice/Domain).
        $wa     = $this->getDocument()->getWebAssetManager();
        $jsPath = JPATH_ROOT . '/administrator/components/com_mothership/assets/js/ticket-edit.js';
        $wa->useScript('jquery');
        $wa->registerAndUseScript(
            'com_mothership.ticket-edit',
            'administrator/components/com_mothership/assets/js/ticket-edit.js',
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

        $isNew = empty($this->item->id);
        $canDo = $this->canDo;

        ToolbarHelper::title(
            $isNew ? Text::_('COM_MOTHERSHIP_MANAGER_TICKET_NEW') : Text::_('COM_MOTHERSHIP_MANAGER_TICKET_EDIT'),
            'ticket mothership-tickets'
        );

        $toolbar = $this->getDocument()->getToolbar();

        if ($canDo->get('core.edit') || $canDo->get('core.create')) {
            $toolbar->apply('ticket.apply');

            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveGroup->configure(
                function (Toolbar $childBar) use ($canDo) {
                    if ($canDo->get('core.edit') || $canDo->get('core.create')) {
                        $childBar->save('ticket.save');
                    }
                    if ($canDo->get('core.create')) {
                        $childBar->save2new('ticket.save2new');
                    }
                }
            );
        }

        $toolbar->cancel('ticket.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
