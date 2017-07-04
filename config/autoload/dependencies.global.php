<?php

use Zend\Expressive\Container;
use Zend\Expressive\Delegate;
use Zend\Expressive\Helper;
use Zend\Expressive\Middleware;
use Zend\Expressive\Application;
use BS\Factory\PipelineAndRoutesDelegator;

return [
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
            Application::class => Container\ApplicationFactory::class,
            Delegate\NotFoundDelegate::class => Container\NotFoundDelegateFactory::class,
            Helper\ServerUrlMiddleware::class => Helper\ServerUrlMiddlewareFactory::class,
            Helper\UrlHelper::class => Helper\UrlHelperFactory::class,
            Helper\UrlHelperMiddleware::class => Helper\UrlHelperMiddlewareFactory::class,

            Zend\Stratigility\Middleware\ErrorHandler::class => Container\ErrorHandlerFactory::class,
            Middleware\ErrorResponseGenerator::class => Container\ErrorResponseGeneratorFactory::class,
            Middleware\NotFoundHandler::class => Container\NotFoundHandlerFactory::class,

            \BS\Middleware\AuthenticationMiddleware::class => \BS\Factory\InvokableFactory::class,
            \BS\Middleware\ControllerMiddleware::class => \BS\Factory\ControllerMiddlewareFactory::class
        ],
        'aliases' => [
            'Zend\Expressive\Delegate\DefaultDelegate' => Delegate\NotFoundDelegate::class
        ],
    ],
];
