<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default Controller for com_mothership
 */
class DisplayController extends BaseController
{
    protected $default_view = 'mothership';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }
}
