<?php
namespace TrevorBice\Component\Mothership\Site\View\Proposals;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $proposals;

    public function display($tpl = null)
    {
        /*
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_proposals', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        */
        $this->proposals = $this->getModel()->getItems();
        parent::display($tpl);
    }
}