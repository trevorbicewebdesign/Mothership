<?php
namespace TrevorBice\Component\Mothership\Site\View\Payment;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $paymentOptions = [];

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_payments', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Payment not found', 404);
        }

        parent::display($tpl);
    }
}
