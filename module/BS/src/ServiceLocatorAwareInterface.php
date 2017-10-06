<?php

namespace BS;

use Interop\Container\ContainerInterface;

interface ServiceLocatorAwareInterface
{
    public function getServiceLocator();

    public function setServiceLocator(ContainerInterface $serviceLocator);
}