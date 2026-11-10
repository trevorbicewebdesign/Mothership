<?php
namespace TrevorBice\Component\Mothership\Site\View\Clients;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $clients;

    public function display($tpl = null)
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user || $user->guest) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->clients = $this->getModel()->getItems();
        parent::display($tpl);
    }
}