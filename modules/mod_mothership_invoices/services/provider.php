<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_mothership_invoices
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
 * The Mothership open-invoices module service provider.
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\TrevorBice\\Module\\MothershipInvoices'));
        $container->registerServiceProvider(new HelperFactory('\\TrevorBice\\Module\\MothershipInvoices\\Administrator\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
