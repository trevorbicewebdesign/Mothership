<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

require_once JPATH_ADMINISTRATOR . '/components/com_mothership/src/Field/ClientList.php';
require_once JPATH_ADMINISTRATOR . '/components/com_mothership/src/Field/AccountList.php';

require_once JPATH_ADMINISTRATOR . '/components/com_mothership/vendor/autoload.php';

/**
 * Joomla 5 Service Provider for com_mothership
 */
return new class implements ServiceProviderInterface {

    public function register(Container $container): void {
        // Register MVC Factory
        $container->registerServiceProvider(new MVCFactory('\\TrevorBice\\Component\\Mothership'));

        // Register Component Dispatcher
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\TrevorBice\\Component\\Mothership'));

        // Register Component Interface
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
