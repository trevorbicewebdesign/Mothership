<?php
namespace TrevorBice\Component\Mothership\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Router\Route;
use Joomla\Event\Event;



/**
 * ComponentDispatcher class for com_mothership
 *
 * @since  5.2.3
 */
class Dispatcher extends ComponentDispatcher
{
    // dispatch()
    public function dispatch()
    {
        PluginHelper::importPlugin('mothership-payment');

        // Manually trigger the onAfterInitialise event
        $dispatcher = Factory::getApplication()->getDispatcher();
        $event = new Event('onAfterInitialise');
        $dispatcher->dispatch('onAfterInitialise', $event);

        return parent::dispatch();

    }
}
