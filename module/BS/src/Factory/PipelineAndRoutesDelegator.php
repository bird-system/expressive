<?php

namespace BS\Factory;

use Psr\Container\ContainerInterface;
use BS\Middleware\AuthenticationMiddleware;
use BS\Middleware\ControllerMiddleware;
use Zend\Expressive\Application;
use Zend\Expressive\Helper\ServerUrlMiddleware;
use Zend\Expressive\Helper\UrlHelperMiddleware;
use Zend\Expressive\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Stratigility\Middleware\ErrorHandler;

class PipelineAndRoutesDelegator
{
    /**
     * @param ContainerInterface $container
     * @param string $serviceName Name of the service being created.
     * @param callable $callback Creates and returns the service.
     * @return Application
     */
    public function __invoke(ContainerInterface $container, $serviceName, callable $callback)
    {
        /** @var $app Application */
        $app = $callback();

        // Setup pipeline:
        $app->pipe(ErrorHandler::class);
        $app->pipe(ServerUrlMiddleware::class);
        $app->pipeRoutingMiddleware();
        $app->pipe(ImplicitHeadMiddleware::class);
        $app->pipe(ImplicitOptionsMiddleware::class);
        $app->pipe(UrlHelperMiddleware::class);
        $app->pipe(AuthenticationMiddleware::class);
        $app->pipeDispatchMiddleware();
        $app->pipe(NotFoundHandler::class);

        // Setup routes:
        $app->route(BASE_URI . '/:module/:controller[/:action][/:id]', ControllerMiddleware::class)->setOptions([
            'constraints' => [
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]+',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]+',
                'id' => '[\\w_.-]*'
            ],
        ]);

        return $app;
    }
}
