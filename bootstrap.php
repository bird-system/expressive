<?php
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;

define('PRODUCTION', 'production');
define('DEVELOPMENT', 'development');
define('TESTING', 'testing');

defined('APP_ENVIRONMENT') ||
define('APP_ENVIRONMENT', isset($_ENV['APP_ENVIRONMENT']) ? $_ENV['APP_ENVIRONMENT'] : (
isset($_SERVER['APP_ENVIRONMENT']) ? $_SERVER['APP_ENVIRONMENT'] : PRODUCTION
));

error_reporting(E_ALL & ~E_STRICT);
if (APP_ENVIRONMENT == DEVELOPMENT) {
    ini_set('display_errors', true);
} else {
    error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', false);
}

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

chdir(__DIR__);
require_once('vendor/autoload.php');

defined('APP_ROOT') || define('APP_ROOT', __DIR__);
defined('BASE_URI') || define('BASE_URI', '/api');