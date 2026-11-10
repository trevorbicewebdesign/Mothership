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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryInterface;
use TrevorBice\Component\Mothership\Administrator\Helper\ClientHelper;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

\defined('_JEXEC') or die;

/**
 * Person page actions: give access to another client, re-invite, or remove access.
 */
class PortaluserController extends BaseController
{
    public function grant(): void
    {
        $userId   = $this->guard();
        $clientId = $this->input->getInt('client_id');
        $email    = (string) Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId)->email;

        try {
            $ownerId    = $clientId ? (int) (ClientHelper::getClient($clientId)->owner_user_id ?? 0) : 0;
            $onBehalfOf = ($this->input->getInt('on_behalf_of_owner') === 1 && $ownerId) ? $ownerId : null;

            ClientAccess::invite($clientId, '', $email, null, $onBehalfOf);
            $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_PORTALUSER_GRANTED'), 'message');
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->back($userId);
    }

    public function remove(): void
    {
        $userId = $this->guard();

        try {
            ClientAccess::remove($this->ownedRowId($userId));
            $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_CLIENT_USERS_REMOVED'), 'message');
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->back($userId);
    }

    public function resend(): void
    {
        $userId = $this->guard();

        try {
            $sent = ClientAccess::resendInvite($this->ownedRowId($userId));
            $this->app->enqueueMessage(
                Text::_($sent['emailed'] ? 'COM_MOTHERSHIP_CLIENT_USERS_RESENT' : 'COM_MOTHERSHIP_CLIENT_USERS_RESEND_FAILED'),
                $sent['emailed'] ? 'message' : 'warning'
            );
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->back($userId);
    }

    /** Token and permission; returns the person's user id. */
    private function guard(): int
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_mothership')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
        }

        $userId = $this->input->getInt('id');

        if (!$userId) {
            throw new \Exception(Text::_('COM_MOTHERSHIP_PORTALUSER_NOT_FOUND'), 404);
        }

        return $userId;
    }

    /** The posted access row, when it belongs to this person. */
    private function ownedRowId(int $userId): int
    {
        $row = ClientAccess::accessRow($this->input->getInt('access_id'));

        if ((int) $row->user_id !== $userId) {
            throw new \RuntimeException(Text::_('COM_MOTHERSHIP_PORTALUSER_NOT_THIS_PERSON'));
        }

        return (int) $row->id;
    }

    private function back(int $userId): void
    {
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=portaluser&id=' . $userId, false));
    }
}
