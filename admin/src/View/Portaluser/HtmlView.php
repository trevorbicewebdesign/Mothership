<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\View\Portaluser;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * One person's portal access across every client, grouped by role.
 */
class HtmlView extends BaseHtmlView
{
    /** The Joomla user. */
    protected $person;

    /** [role => entries from ClientAccess::personAccess()], in role order. */
    protected $byRole = [];

    /** Clients this person could still be given access to, [id => name]. */
    protected $grantableClients = [];

    /** Whether the current user may change this person's access. */
    protected $canEdit = false;

    public function display($tpl = null): void
    {
        $app    = Factory::getApplication();
        $userId = $app->getInput()->getInt('id');
        $db     = Factory::getContainer()->get(DatabaseInterface::class);

        $this->person = $userId ? $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name', 'username', 'email', 'block', 'lastvisitDate', 'registerDate']))
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $userId, ParameterType::INTEGER)
        )->loadObject() : null;

        if (!$this->person) {
            $app->enqueueMessage(Text::_('COM_MOTHERSHIP_PORTALUSER_NOT_FOUND'), 'error');
            $app->redirect(Route::_('index.php?option=com_mothership&view=portalusers', false));

            return;
        }

        $this->canEdit = (bool) ContentHelper::getActions('com_mothership')->get('core.edit');

        $order = [ClientAccess::ROLE_OWNER, ClientAccess::ROLE_FULL, ClientAccess::ROLE_ACCOUNT, ClientAccess::ROLE_BILLING];
        $taken = [];

        foreach ($order as $role) {
            $this->byRole[$role] = [];
        }

        foreach (ClientAccess::personAccess((int) $this->person->id) as $entry) {
            $this->byRole[$entry->role][] = $entry;
            $taken[$entry->client_id]     = true;
        }

        $this->byRole = array_filter($this->byRole);

        $clients = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__mothership_clients'))
                ->order($db->quoteName('name') . ' ASC')
        )->loadObjectList();

        foreach ($clients as $client) {
            if (empty($taken[(int) $client->id])) {
                $this->grantableClients[(int) $client->id] = (string) $client->name;
            }
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        ToolbarHelper::title(Text::sprintf('COM_MOTHERSHIP_PORTALUSER_TITLE', $this->escape($this->person->name)), 'users mothership-portalusers');
        ToolbarHelper::link(Route::_('index.php?option=com_mothership&view=portalusers', false), 'JTOOLBAR_BACK', 'arrow-left');

        if (Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_users')) {
            ToolbarHelper::link(Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $this->person->id, false), 'COM_MOTHERSHIP_PORTALUSER_EDIT_LOGIN', 'user');
        }
    }
}
