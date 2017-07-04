<?php

namespace BS\Middleware;

use BS\Authentication\AuthenticationService;
use BS\ServiceLocatorAwareInterface;
use BS\Traits\ServiceLocatorAwareTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\ServiceManager\ServiceManager;

class AuthenticationMiddleware implements MiddlewareInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    private static $authenticates = [];

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $moduleName = strtolower($request->getAttribute(ControllerMiddleware::ATTR_MODULE_NAME));

        if (!array_key_exists($moduleName, self::$authenticates)) {
            $authenticate = $this->getAuthenticate($moduleName);
        } else {
            $authenticate = self::$authenticates[$moduleName];
        }

        if ($this->serviceLocator instanceof ServiceManager) {
            $this->serviceLocator->setService('AuthService', $authenticate);
        }

        return $delegate->process($request);
    }

    private function getAuthenticate($module)
    {
        return new AuthenticationService();
    }
}