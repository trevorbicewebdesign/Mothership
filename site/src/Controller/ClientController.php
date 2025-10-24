<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

class ClientController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        die('here');
        $this->input->set('view', $this->input->getCmd('view', 'client'));
        parent::display($cachable, $urlparams);
    }

}
