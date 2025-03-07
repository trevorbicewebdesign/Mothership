<?php
namespace TrevorBice\Component\Mothership\Site\View\Invoice;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Invoice not found', 404);
        }

        parent::display($tpl);
    }
}
