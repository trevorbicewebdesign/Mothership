<?php
namespace TrevorBice\Component\Mothership\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

class ProjectController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->input->set('view', $this->input->getCmd('view', 'project'));
        parent::display($cachable, $urlparams);
    }

}
