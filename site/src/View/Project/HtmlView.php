<?php
namespace TrevorBice\Component\Mothership\Site\View\Domain;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Exception;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $domainOptions = [];

    public function display($tpl = null)
    {
        $user = Factory::getUser();
        if (!$user->authorise('mothership.view_projects', 'com_mothership')) {
            echo Text::_('JERROR_ALERTNOAUTHOR');
            return;
        }
        $this->item = $this->getModel()->getItem();

        if (!$this->item) {
            throw new \Exception('Project not found', 404);
        }

        parent::display($tpl);
    }
}
