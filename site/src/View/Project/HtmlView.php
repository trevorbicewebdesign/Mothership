<?php
namespace TrevorBice\Component\Mothership\Site\View\Project;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Exception;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $domainOptions = [];

    public function display($tpl = null)
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        $this->item = $this->getModel()->getItem();

        // Ownership: super users, or the client that owns this project. Missing or
        // inaccessible → redirect to the projects list (no hard 404, no leak).
        $owns = $this->item && \in_array((int) $this->item->client_id, MothershipHelper::getUserClientIds() ?? [], true);

        if (!$this->item || (!$owns && !$user->authorise('core.admin', 'com_mothership'))) {
            $app->enqueueMessage('That project isn’t available on your account.', 'warning');
            $app->redirect(Route::_('index.php?option=com_mothership&view=projects', false));

            return;
        }

        LogHelper::logProjectViewed($this->item->client_id, $this->item->account_id, $this->item->id);

        parent::display($tpl);
    }
}
