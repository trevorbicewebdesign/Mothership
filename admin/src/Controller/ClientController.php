<?php

namespace TrevorBice\Component\Mothership\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Client Controller for com_mothership
 */
class ClientController extends BaseController
{
    protected $default_view = 'client';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display();
    }
}
