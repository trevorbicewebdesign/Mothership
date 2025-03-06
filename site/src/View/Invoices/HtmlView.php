<?php
namespace TrevorBice\Component\Mothership\Site\View\Invoices;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $invoices;

    public function display($tpl = null)
    {
        $this->invoices = $this->getModel()->getItems();
        parent::display($tpl);
    }
}