<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

class InviteController extends BaseController
{
    /** Set the invited person's password, then sign them in. */
    public function accept()
    {
        $this->checkToken();

        $input    = $this->input;
        $token    = preg_replace('/[^a-f0-9]/', '', $input->getString('token', ''));
        $password = (string) $input->get('password', '', 'RAW');
        $confirm  = (string) $input->get('password2', '', 'RAW');

        try {
            $invite = ClientAccess::acceptInvite($token, $password, $confirm);
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=invite&token=' . $token, false));

            return;
        }

        // Someone else signed in on this browser would otherwise stay signed in.
        $identity = $this->app->getIdentity();

        if ($identity && !$identity->guest && (int) $identity->id !== (int) $invite->user_id) {
            $this->app->logout((int) $identity->id);
        }

        if ($this->app->login(['username' => $invite->username, 'password' => $password], ['remember' => false, 'silent' => true]) === true) {
            $this->app->enqueueMessage(Text::sprintf('COM_MOTHERSHIP_INVITE_ACCEPTED', $invite->client_name), 'success');
            $this->setRedirect(Route::_('index.php?option=com_mothership&view=clients', false));

            return;
        }

        $this->app->enqueueMessage(Text::_('COM_MOTHERSHIP_INVITE_ACCEPTED_SIGN_IN'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_users&view=login', false));
    }
}
