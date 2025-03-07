<?php
namespace TrevorBice\Component\Mothership\Site\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $totalOutstanding;

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_invoices', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->totalOutstanding = $this->getModel()->getTotalOutstanding();
        parent::display($tpl);
    }
}