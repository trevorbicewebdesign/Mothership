<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_tickets
 *
 * @copyright   (C) 2026 Trevor Bice
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The Mothership latest-tickets module service provider.
 */
return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\TrevorBice\\Module\\MothershipTickets'));
        $container->registerServiceProvider(new HelperFactory('\\TrevorBice\\Module\\MothershipTickets\\Administrator\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
