<?php

declare(strict_types=1);

namespace Tests\Support;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Base for integration tests that reach com_mothership's models and services through the
 * booted component rather than by requiring files. The headless bootstrap has no request, so
 * the component-path constants the form layer expects are defined here.
 */
abstract class MothershipComponentTest extends \Codeception\Test\Unit
{
    protected IntegrationTester $tester;

    protected MVCFactoryInterface $mvc;

    protected function _before()
    {
        if (!\defined('JPATH_COMPONENT')) {
            \define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_mothership');
            \define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
            \define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_mothership');
        }

        // Extension namespaces (com_mothership and friends), so tests can use their classes by name.
        // The headless bootstrap does not load them.
        if (!class_exists(\JNamespacePsr4Map::class, false)) {
            require_once JPATH_LIBRARIES . '/namespacemap.php';
            (new \JNamespacePsr4Map())->load();
        }

        // There is no request in the CLI, so anything reaching for the host (Joomla's Uri) has one.
        $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['SCRIPT_NAME']    = '/index.php';
        $_SERVER['PHP_SELF']       = '/index.php';
        $_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/index.php';
        $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $this->mvc = Factory::getApplication()->bootComponent('com_mothership')->getMVCFactory();
    }

    /** An administrator model that reads nothing from the (non-existent) request. */
    protected function adminModel(string $name)
    {
        $model = $this->mvc->createModel($name, 'Administrator', ['ignore_request' => true]);
        $this->assertNotNull($model, "com_mothership has no Administrator model named {$name}");

        return $model;
    }
}
