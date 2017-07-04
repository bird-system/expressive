<?php

namespace BS\Factory;

use Interop\Container\ContainerInterface;
use BS\Traits\InjectServiceFromAwareInterfaceTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class InvokableFactory implements FactoryInterface
{
    use InjectServiceFromAwareInterfaceTrait;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $instance = new $requestedName();

        $this->checkAwareInterface($instance, $container);

        return $instance;
    }
}