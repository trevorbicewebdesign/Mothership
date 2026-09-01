<?php
namespace TrevorBice\Component\Mothership\Site\View\Ticket;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $replies = [];
    public $attachments = [];
    public $isAdmin = false;

    public function display($tpl = null)
    {
        $app   = Factory::getApplication();
        $user  = $app->getIdentity();
        $model = $this->getModel();

        $this->item = $model->getItem();

        // Ownership: super users, or the client that owns this ticket. A missing or
        // inaccessible ticket (e.g. the post-login return pointed at a ticket on a
        // different account) sends the user to their own tickets list rather than a
        // hard 404 — without confirming whether the ticket exists.
        $clientIds     = MothershipHelper::getUserClientIds();
        $owns          = $this->item && $clientIds && in_array((int) $this->item->client_id, $clientIds, true);
        $this->isAdmin = (bool) $user->authorise('core.admin', 'com_mothership');

        if (!$this->item || (!$owns && !$this->isAdmin)) {
            $app->enqueueMessage('That ticket isn’t available on your account.', 'warning');
            $app->redirect(Route::_('index.php?option=com_mothership&view=tickets', false));

            return;
        }

        $this->replies     = $model->getComments($this->item->id);
        $this->attachments = $model->getAttachments($this->item->id);

        parent::display($tpl);
    }
}
