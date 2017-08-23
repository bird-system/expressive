<?php

namespace BS;

use BS\Factory\ControllerAbstractFactory;
use BS\Factory\InvokableFactory;
use BS\Factory\TableGatewayAbstractFactory;
use BS\Utility\Measure;
use EnliteMonolog\Service\MonologServiceAbstractFactory;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\I18n\Translator\TranslatorServiceFactory;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'session' => [
                //'remember_me_seconds' => 8 * 3600, // cookie_lifetime, remember session from browser cookie
                'gc_maxlifetime' => 8 * 3600, // 8 hours session expiry
                'use_cookies' => true,
                'cookie_httponly' => true,
            ],
            'translator' => [
                'locale' => 'en_GB',
                'translation_file_patterns' => [
                    [
                        'type' => 'gettext',
                        'base_dir' => __DIR__ . '/../../language',
                        'pattern' => '%s.mo',
                    ],
                ],
            ],
            'EnliteMonolog' => [
                // Logger name
                'logger' => [
                    // name of
                    'name' => 'DEBUG',
                    // Handlers, it can be service manager alias(string) or config(array)
                    'handlers' => [
                        'default' => [
                            'name' => (APP_ENVIRONMENT != PRODUCTION) ? (('cli' == php_sapi_name()) ? StreamHandler::class : FirePHPHandler::class) : NullHandler::class,
                            'args' => ('cli' == php_sapi_name()) ? ['stream' => 'php://stdout'] : [],
                        ],
                    ],
                ],
            ],
            'service_manager' => [
                'factories' => [
                    Measure::class => InvokableFactory::class,
                    TranslatorInterface::class => TranslatorServiceFactory::class
                ],
                'aliases' => [
                    'Measure' => Measure::class,
                    'translator' => TranslatorInterface::class
                ],
                'abstract_factories' => [
                    ControllerAbstractFactory::class,
                    TableGatewayAbstractFactory::class,
                    MonologServiceAbstractFactory::class
                ]
            ],
            'templates' => [
                'paths' => [
                    __NAMESPACE__ => __DIR__ . '/../view',
                    'error' => __DIR__ . '/../view/error'
                ]
            ]
        ];
    }
}