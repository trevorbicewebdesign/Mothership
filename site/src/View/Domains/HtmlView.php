<?php
namespace TrevorBice\Component\Mothership\Site\View\Domains;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $domains;

    public function display($tpl = null)
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user || $user->guest) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->domains = $this->getModel()->getItems();
        parent::display($tpl);
    }
}