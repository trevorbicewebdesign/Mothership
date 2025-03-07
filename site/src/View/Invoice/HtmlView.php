<?php
namespace TrevorBice\Component\Mothership\Site\View\Invoice;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_invoices', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Invoice not found', 404);
        }

        parent::display($tpl);
    }
}
