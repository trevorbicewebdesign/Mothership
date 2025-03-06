<?php
namespace TrevorBice\Component\Mothership\Site\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $totalOutstanding;

    public function display($tpl = null)
    {
        $this->totalOutstanding = $this->getModel()->getTotalOutstanding();
        parent::display($tpl);
    }
}