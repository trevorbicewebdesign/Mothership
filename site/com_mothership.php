<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\DI\Container;
use Joomla\CMS\Router\SiteRouter;
use TrevorBice\Component\Mothership\Site\Dispatcher\Dispatcher;

return new class implements ComponentInterface {
    public function boot(Container $container) {}

    public function registerRoutes(SiteRouter $router) {}

    public function getDispatcher(\Joomla\CMS\Application\CMSApplicationInterface $application): \Joomla\CMS\Dispatcher\DispatcherInterface
    {
        return $application->get(ComponentDispatcherFactoryInterface::class)
            ->createDispatcher('com_mothership');
    }
};
