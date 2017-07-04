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
use Zend\Db\Adapter\AdapterServiceFactory;
use BS\Utility\Utility;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\I18n\Translator\TranslatorServiceFactory;

class ConfigProvider
{
    public function __invoke()
    {
        $_ENV['DB_HOST'] = Utility::getEnvValue('DB_HOST', 'db');
        $_ENV['DB_DATABASE'] = Utility::getEnvValue('DB_DATABASE', 'send_for_you');
        $_ENV['DB_USERNAME'] = Utility::getEnvValue('DB_USERNAME', 'root');
        $_ENV['DB_PASSWORD'] = Utility::getEnvValue('DB_PASSWORD', 'aeiEJA93Kadki93f');
        $_ENV['DB_PORT'] = Utility::getEnvValue('DB_PORT', '3308');

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
                    'db' => AdapterServiceFactory::class,
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
            ]
        ];
    }
}