<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Router\RouterView;
use Joomla\CMS\Router\ViewConfiguration;

return function (RouterView $router) {
    $dashboard = new ViewConfiguration('dashboard');
    $router->registerView($dashboard);

    $invoices = new ViewConfiguration('invoices');
    $router->registerView($invoices);
};
