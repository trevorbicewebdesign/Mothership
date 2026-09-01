<?php
namespace TrevorBice\Component\Mothership\Site\View\Tickets;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use TrevorBice\Component\Mothership\Site\Helper\MothershipHelper;

class HtmlView extends BaseHtmlView
{
    public $items = [];
    public $accounts = [];
    public $projects = [];
    public $isAdmin = false;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        $this->items   = $this->getModel()->getItems();
        $this->isAdmin = (bool) $app->getIdentity()->authorise('core.admin', 'com_mothership');

        // The client's accounts — used to populate the "New ticket" form.
        $clientIds = MothershipHelper::getUserClientIds();
        if ($clientIds) {
            $db  = Factory::getContainer()->get('DatabaseDriver');
            $ids = implode(',', array_map('intval', (array) $clientIds));
            $db->setQuery(
                'SELECT id, name FROM ' . $db->quoteName('#__mothership_accounts')
                . ' WHERE client_id IN (' . $ids . ') ORDER BY name'
            );
            $this->accounts = $db->loadObjectList();

            $db->setQuery(
                'SELECT id, name, account_id FROM ' . $db->quoteName('#__mothership_projects')
                . ' WHERE client_id IN (' . $ids . ') ORDER BY name'
            );
            $this->projects = $db->loadObjectList();
        }

        parent::display($tpl);
    }
}
