<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default Controller for com_mothership
 */
class DisplayController extends BaseController
{
    protected $default_view = 'clients'; // Change this if 'mothership' is incorrect

    public function display($cachable = false, $urlparams = [])
    {
        $input = $this->app->input;
        $view  = $input->getCmd('view', $this->default_view);
        $input->set('view', $view);

        return parent::display();
    }
}
