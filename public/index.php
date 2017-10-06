<?php
ini_set('html_errors', 0);
ini_set('memory_limit', '1024M');

// Setup autoloading
require dirname(__DIR__) . '/bootstrap.php';

// Run the application!
/** Cronjobs don't need all the extra's **/
if (!defined('_CRONJOB_') || _CRONJOB_ == false) {
    /** @var \Interop\Container\ContainerInterface $container */
    $container = require APP_ROOT . '/config/container.php';

    /** @var \Zend\Expressive\Application $app */
    $app = $container->get(\Zend\Expressive\Application::class);
    $app->run();
}