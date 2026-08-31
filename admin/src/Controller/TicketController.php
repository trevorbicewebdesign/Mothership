<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Helper\AccountHelper;
use TrevorBice\Component\Mothership\Administrator\Helper\ProjectHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Ticket form controller.
 */
class TicketController extends FormController
{
    protected $default_view = 'ticket';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }

    /** AJAX: accounts belonging to the selected client (client → account drill-down). */
    public function getAccountsList()
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_mothership')) {
            $app->setHeader('status', 403, true)->sendHeaders();
            echo json_encode([]);
            $app->close();
        }
        $clientId = $app->getInput()->getInt('client_id');
        echo json_encode(AccountHelper::getAccountListOptions($clientId));
        $app->close();
    }

    /** AJAX: projects belonging to the selected account (account → project drill-down). */
    public function getProjectsList()
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_mothership')) {
            $app->setHeader('status', 403, true)->sendHeaders();
            echo json_encode([]);
            $app->close();
        }
        $accountId = $app->getInput()->getInt('account_id');
        echo json_encode(ProjectHelper::getProjectListOptions($accountId));
        $app->close();
    }

    public function save($key = null, $urlVar = null)
    {
        // CSRF protection.
        $this->checkToken();

        $app   = Factory::getApplication();
        $input = $app->getInput();
        $data  = $input->get('jform', [], 'array');
        $model = $this->getModel('Ticket');

        // ACL: creating requires core.create, editing an existing ticket requires core.edit.
        $user  = $app->getIdentity();
        $isNew = empty($data['id']);
        $can   = $isNew ? $user->authorise('core.create', 'com_mothership')
                        : $user->authorise('core.edit', 'com_mothership');

        if (!$can) {
            throw new \Joomla\CMS\Access\Exception\NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if (!$model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_TICKET_SAVE_FAILED'), 'error');
            $app->enqueueMessage($model->getError(), 'error');
            $id = !empty($data['id']) ? (int) $data['id'] : (int) $model->getState($model->getName() . '.id');
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=ticket&layout=edit&id={$id}", false));
            return false;
        }

        $app->enqueueMessage(Text::_('COM_MOTHERSHIP_TICKET_SAVED_SUCCESSFULLY'), 'message');

        $app->setUserState('com_mothership.edit.ticket.data', null);

        $task = $input->getCmd('task');
        $id   = !empty($data['id']) ? (int) $data['id'] : (int) $model->getState($model->getName() . '.id');

        if ($task === 'apply') {
            $this->setRedirect(Route::_("index.php?option=com_mothership&view=ticket&layout=edit&id={$id}", false));
        } elseif ($task === 'save2new') {
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=ticket&layout=edit', false));
        } else {
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=tickets', false));
        }

        return true;
    }

    public function cancel($key = null)
    {
        parent::cancel($key);

        Factory::getApplication()->setUserState('com_mothership.edit.ticket.data', null);
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=tickets', false));

        return true;
    }
}
