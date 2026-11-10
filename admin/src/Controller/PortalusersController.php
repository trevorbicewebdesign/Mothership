<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Component\Mothership\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

\defined('_JEXEC') or die;

/**
 * Portal Users list actions. The checkbox values are user ids. Ownership is never changed here;
 * it lives on the client record.
 */
class PortalusersController extends BaseController
{
    /** Takes away every client access the selected people were given. */
    public function remove(): void
    {
        $people  = 0;
        $nothing = 0;

        foreach ($this->selectedUserIds() as $userId) {
            $rows = ClientAccess::accessRowsForUser($userId);

            foreach ($rows as $row) {
                ClientAccess::remove((int) $row->id);
            }

            $rows ? $people++ : $nothing++;
        }

        if ($people) {
            $this->app->enqueueMessage(Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_REMOVED', $people), 'message');
        }

        if ($nothing) {
            $this->app->enqueueMessage(Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_NOTHING_TO_REMOVE', $nothing), 'warning');
        }

        $this->back();
    }

    /** Sends a fresh link for every invite the selected people haven't accepted yet. */
    public function resend(): void
    {
        $sent    = 0;
        $failed  = 0;
        $nothing = 0;

        foreach ($this->selectedUserIds() as $userId) {
            $pending = array_filter(ClientAccess::accessRowsForUser($userId), static fn ($row) => $row->accepted === null);

            if (!$pending) {
                $nothing++;
                continue;
            }

            foreach ($pending as $row) {
                ClientAccess::resendInvite((int) $row->id)['emailed'] ? $sent++ : $failed++;
            }
        }

        if ($sent) {
            $this->app->enqueueMessage(Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_RESENT', $sent), 'message');
        }

        // Each failed send has already put its own warning on screen (EmailService does that in
        // the administrator), so only the count is added here.
        if ($failed) {
            $this->app->enqueueMessage(Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_RESEND_FAILED', $failed), 'error');
        }

        if ($nothing) {
            $this->app->enqueueMessage(Text::plural('COM_MOTHERSHIP_PORTALUSERS_N_NOTHING_TO_RESEND', $nothing), 'warning');
        }

        $this->back();
    }

    /** Pending invites panel: send a fresh link for one invite. */
    public function resendInvite(): void
    {
        $this->guard();

        try {
            $sent = ClientAccess::resendInvite($this->input->getInt('access_id'));
            $this->app->enqueueMessage(
                Text::_($sent['emailed'] ? 'COM_MOTHERSHIP_CLIENT_USERS_RESENT' : 'COM_MOTHERSHIP_CLIENT_USERS_RESEND_FAILED'),
                $sent['emailed'] ? 'message' : 'warning'
            );
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->back();
    }

    /** Pending invites panel: withdraw an invite by removing the access it would grant. */
    public function cancelInvite(): void
    {
        $this->guard();

        try {
            $row = ClientAccess::accessRow($this->input->getInt('access_id'));

            if ($row->accepted !== null) {
                throw new \RuntimeException(Text::_('COM_MOTHERSHIP_PORTALUSERS_ALREADY_ACCEPTED'));
            }

            ClientAccess::remove((int) $row->id);
            $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_PORTALUSERS_INVITE_CANCELLED'), 'message');
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->back();
    }

    /** Token and permission. */
    private function guard(): void
    {
        $this->checkToken();

        if (!$this->app->getIdentity()->authorise('core.edit', 'com_mothership')) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
        }
    }

    /** Checks the token and permission, and returns the selected user ids. */
    private function selectedUserIds(): array
    {
        $this->guard();

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->get('cid', [], 'array')))));

        if (!$ids) {
            $this->app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
        }

        return $ids;
    }

    private function back(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_mothership&view=portalusers', false));
    }
}
