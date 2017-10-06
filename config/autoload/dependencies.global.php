<?php

use Zend\Expressive\Container;
use Zend\Expressive\Delegate;
use Zend\Expressive\Helper;
use Zend\Expressive\Middleware;
use Zend\Expressive\Application;
use BS\Utility\Utility;
use Zend\Db\Adapter\AdapterServiceFactory;
use BS\Factory\PipelineAndRoutesDelegator;

$_ENV['DB_HOST'] = Utility::getEnvValue('DB_HOST', 'db');
$_ENV['DB_DATABASE'] = Utility::getEnvValue('DB_DATABASE', 'database');
$_ENV['DB_USERNAME'] = Utility::getEnvValue('DB_USERNAME', 'user');
$_ENV['DB_PASSWORD'] = Utility::getEnvValue('DB_PASSWORD', 'password');
$_ENV['DB_PORT'] = Utility::getEnvValue('DB_PORT', '3306');

return [
    'db' => [
        'driver' => 'Pdo_Mysql',
        'hostname' => gethostbyname($_ENV['DB_HOST']),
        'database' => $_ENV['DB_DATABASE'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'port' => $_ENV['DB_PORT'],
        'driver_options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            \PDO::ATTR_PERSISTENT => 'cli' == php_sapi_name() ? true : false,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ],
    ],
    'service_manager' => [
        'delegators' => [
            Application::class => [
                PipelineAndRoutesDelegator::class,
            ],
        ],
        'invokables' => [
            \Zend\Expressive\Router\RouterInterface::class => \Zend\Expressive\Router\ZendRouter::class,
            Helper\ServerUrlHelper::class => Helper\ServerUrlHelper::class
        ],
        'factories' => [
            'db' => AdapterServiceFactory::class,
            Application::class => Container\ApplicationFactory::class,
            Delegate\NotFoundDelegate::class => Container\NotFoundDelegateFactory::class,
            Helper\ServerUrlMiddleware::class => Helper\ServerUrlMiddlewareFactory::class,
            Helper\UrlHelper::class => Helper\UrlHelperFactory::class,
            Helper\UrlHelperMiddleware::class => Helper\UrlHelperMiddlewareFactory::class,

            Zend\Stratigility\Middleware\ErrorHandler::class => Container\ErrorHandlerFactory::class,
            Middleware\ErrorResponseGenerator::class => Container\ErrorResponseGeneratorFactory::class,
            Middleware\NotFoundHandler::class => Container\NotFoundHandlerFactory::class,

            \BS\Middleware\AuthenticationMiddleware::class => \BS\Factory\InvokableFactory::class,
            \BS\Middleware\ControllerMiddleware::class => \BS\Factory\ControllerMiddlewareFactory::class,
            \BS\Middleware\ResponseMiddleware::class => \BS\Factory\InvokableFactory::class,

            \Zend\Expressive\Template\TemplateRendererInterface::class => \Zend\Expressive\ZendView\ZendViewRendererFactory::class
        ],
        'aliases' => [
            'Zend\Expressive\Delegate\DefaultDelegate' => Delegate\NotFoundDelegate::class
        ],
    ],
];
