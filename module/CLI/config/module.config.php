<?php

namespace CLI;

use BS\Factory\InvokableFactory;
use CLI\Controller\CacheController;
use CLI\Controller\CodeGeneratorController;
use CLI\Controller\DockerComposeController;
use CLI\Controller\PHPUnitController;

return [
    'controllers' => [
        'factories' => [
            CodeGeneratorController::class => InvokableFactory::class,
            CacheController::class => InvokableFactory::class,
            DockerComposeController::class => InvokableFactory::class,
            PHPUnitController::class => InvokableFactory::class,
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'code-default' => [
                    'options' => [
                        'route' => 'code [<action>]',
                        'defaults' => [
                            'controller' => CodeGeneratorController::class,
                            'action' => 'help',
                        ],
                    ],
                ],
                'code-generate' => [
                    'options' => [
                        'route' => 'code generate [--all|--source|--test]:type ' .
                            '[--modules=] [--tables=] [--output] [--force-overwrite|-f]',
                        'defaults' => [
                            'controller' => CodeGeneratorController::class,
                            'action' => 'generate',
                        ],
                    ],
                ],
                'code-generate-source' => [
                    'options' => [
                        'route' => 'code generate-source [--modules=] [--tables=] [--output] [--force-overwrite|-f]',
                        'defaults' => [
                            'controller' => CodeGeneratorController::class,
                            'action' => 'generate-source',
                        ],
                    ],
                ],
                'code-generate-test' => [
                    'options' => [
                        'route' => 'code generate-test [--modules=] [--tables=] [--output] [--force-overwrite|-f]',
                        'defaults' => [
                            'controller' => CodeGeneratorController::class,
                            'action' => 'generate-test',
                        ],
                    ],
                ],
                'cache-default' => [
                    'options' => [
                        'route' => 'cache [<action>]',
                        'defaults' => [
                            'controller' => CacheController::class,
                            'action' => 'help',
                        ],
                    ],
                ],
                'cache-clear' => [
                    'options' => [
                        'route' => 'cache clear [all|metadata|default]:mode',
                        'defaults' => [
                            'controller' => CacheController::class,
                            'action' => 'clear',
                        ],
                    ],
                ],
                'phpunit-show-testsuites' => [
                    'options' => [
                        'route' => 'phpunit show-testsuites <file>',
                        'defaults' => [
                            'controller' => PHPUnitController::class,
                            'action' => 'show-testsuites',
                        ],
                    ],
                ],
                'docker-compose-generate-name' => [
                    'options' => [
                        'route' => 'docker-compose generate-name <test-suite>',
                        'defaults' => [
                            'controller' => PHPUnitController::class,
                            'action' => 'generate-name',
                        ],
                    ],
                ],
                'docker-compose-generate-content' => [
                    'options' => [
                        'route' => 'docker-compose generate-content <test-suite> <file-seed>  [v2]:version',
                        'defaults' => [
                            'controller' => DockerComposeController::class,
                            'action' => 'generate-content',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
