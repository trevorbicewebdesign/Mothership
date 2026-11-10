<?php
namespace TrevorBice\Component\Mothership\Site\View\Invite;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use TrevorBice\Component\Mothership\Administrator\Service\ClientAccess;

/**
 * Where an invited person sets their password. Open to guests; the token is the credential.
 */
class HtmlView extends BaseHtmlView
{
    /** The pending invite, or null when the link is unknown, used or expired. */
    public $invite;

    public $token = '';

    public $minLength = 12;

    public function display($tpl = null)
    {
        $this->token     = preg_replace('/[^a-f0-9]/', '', Factory::getApplication()->getInput()->getString('token', ''));
        $this->invite    = $this->token !== '' ? ClientAccess::findInvite($this->token) : null;
        $this->minLength = ClientAccess::minimumPasswordLength();

        $this->getDocument()->setTitle(Text::_($this->invite ? 'COM_MOTHERSHIP_INVITE_TITLE' : 'COM_MOTHERSHIP_INVITE_EXPIRED_TITLE'));

        // The link is a credential: keep it out of search engines and other sites' referrer logs.
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->setMetaData('referrer', 'no-referrer');

        parent::display($tpl);
    }
}
