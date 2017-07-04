<?php

namespace BS\Traits;

use Interop\Container\ContainerInterface;

trait ServiceLocatorAwareTrait
{
    /**
     * @var $serviceLocator ContainerInterface
     */
    protected $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }
}