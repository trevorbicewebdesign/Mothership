<?php
namespace TrevorBice\Component\Mothership\Site\View\Payments;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $payments;

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_payments', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->payments = $this->getModel()->getItems();
        parent::display($tpl);
    }
}