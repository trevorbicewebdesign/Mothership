<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Mvc\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            ComponentDispatcherFactoryInterface::class,
            fn($c) => new \Joomla\CMS\Dispatcher\ComponentDispatcherFactory(
                $c,
                $c->get(MVCFactoryInterface::class)
            )
        );

        $container->set(
            MVCFactoryInterface::class,
            fn($c) => new \Joomla\CMS\MVC\Factory\MVCFactory($c)
        );

        $container->set(
            'site::com_mothership.controller',
            fn($c) => new \TrevorBice\Component\Mothership\Site\Controller\DisplayController()
        );
    }
};
