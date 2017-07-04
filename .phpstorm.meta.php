<?php

namespace PHPSTORM_META {

    use Interop\Container\ContainerInterface;

    override(
        ContainerInterface::get(0),
        map([
            'translator' instanceof \Zend\I18n\Translator\Translator,
            'db' instanceof \Zend\Db\Adapter\Adapter
        ])
    );
}