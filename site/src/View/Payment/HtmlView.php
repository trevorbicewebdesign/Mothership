<?php
namespace TrevorBice\Component\Mothership\Site\View\Payment;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper; // Adjust the namespace if LogHelper is located elsewhere

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
        $input = Factory::getApplication()->getInput();
        $this->invoiceId = $input->getInt('invoice_id');

        if (!$this->item) {
            throw new \Exception('Payment not found', 404);
        }

        if ($this->getLayout() === 'default') {
            LogHelper::logPaymentViewed($this->item->client_id, $this->item->account_id, $this->item->id);
        }

        parent::display($tpl);
    }
}
