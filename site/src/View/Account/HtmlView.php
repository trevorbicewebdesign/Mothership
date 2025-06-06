<?php
namespace TrevorBice\Component\Mothership\Site\View\Account;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;
use TrevorBice\Component\Mothership\Administrator\Helper\LogHelper;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $paymentOptions = [];

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_accounts', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Payment not found', 404);
        }

        LogHelper::logAccountViewed($this->item->client_id, $this->item->id);

        parent::display($tpl);
    }
}
