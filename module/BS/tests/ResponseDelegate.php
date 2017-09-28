<?php

namespace BS\Tests;

use BS\Controller\Exception\AppException;
use BS\Exception;
use BS\Middleware\ResponseMiddleware;
use BS\ServiceLocatorAwareInterface;
use BS\Traits\ServiceLocatorAwareTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Delegate\NotFoundDelegate;

class ResponseDelegate implements DelegateInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function process(ServerRequestInterface $request)
    {
        $ResponseMiddleware = $this->serviceLocator->get(ResponseMiddleware::class);
        $result = null;
        try {
            $result = $ResponseMiddleware->process($request, $this->serviceLocator->get(NotFoundDelegate::class));
        } catch (\Exception $e) {
            if ($e instanceof AppException && $e->getPrevious() instanceof \Exception) {
                throw $e->getPrevious();
            }

            throw $e;
        }

        return $result;
    }
}