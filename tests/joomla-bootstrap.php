<?php
// tests/joomla-headless-bootstrap.php

define('_JEXEC', 1);
define('JOOMLA_MINIMUM_PHP', '8.1.0');

if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
    fwrite(STDERR, 'Your CLI PHP version does not meet Joomla’s minimum requirement.');
    exit(1);
}

// Load system defines
$rootDir = dirname(__DIR__); // Adjust if needed
$definesFile = $rootDir . '/defines.php';

if (file_exists($definesFile)) {
    require_once $definesFile;
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', $rootDir);
    require_once JPATH_BASE . '/includes/defines.php';
}

// Check for vendor dependencies and config
if (!file_exists(JPATH_LIBRARIES . '/vendor/autoload.php') || !is_dir(JPATH_ROOT . '/media/vendor')) {
    fwrite(STDERR, "Joomla's vendor dependencies are missing.\n");
    exit(1);
}

if (
    !file_exists(JPATH_CONFIGURATION . '/configuration.php')
    || (filesize(JPATH_CONFIGURATION . '/configuration.php') < 10)
) {
    fwrite(STDERR, "Install Joomla to run integration tests.\n");
    exit(1);
}

// Load the Joomla framework
require_once JPATH_BASE . '/includes/framework.php';

// Boot the DI container & alias services for CLI
use Joomla\CMS\Factory;
use Joomla\Console\Application as JoomlaCliApp;
use Joomla\Session\Session as JoomlaSession;
use Joomla\Session\SessionInterface;

$container = Factory::getContainer();

$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(JoomlaSession::class, 'session.cli')
    ->alias(SessionInterface::class, 'session.cli');

// Instantiate (but do not execute) the CLI application
$app = $container->get(JoomlaCliApp::class);
Factory::$application = $app;

// No $app->execute(); so it remains “headless.”
