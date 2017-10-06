<?php

use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

// Load configuration
$config = require __DIR__ . '/config.php';

// Build container
$container = new ServiceManager();
(new Config($config['service_manager']))->configureServiceManager($container);

// Inject config
$container->setService('config', $config);

return $container;
