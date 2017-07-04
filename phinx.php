<?php
/**
 * User: Allan Sun (allan.sun@bricre.com)
 * Date: 14/12/2015
 * Time: 14:55
 */
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

require_once './bootstrap.php';

$aggregator = new ConfigAggregator([
    new PhpFileProvider(APP_ROOT . '/config/autoload/{,*.}{global,local}.php')
]);

$configs = $aggregator->getMergedConfig();
$db = isset($configs['db']) ? $configs['db'] : [];

if (empty($db)) {
    echo 'no database config';
    exit(1);
}

return [
    "paths" => [
        "migrations" => APP_ROOT . "/db/migrations",
        "seeds" => APP_ROOT . "/db/seeds"
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_database" => "send_for_you",
        "send_for_you" => [
            "adapter" => "mysql",
            "host" => $db['hostname'],
            "name" => $db['database'],
            "user" => $db['username'],
            "pass" => $db['password'],
            "port" => $db['port'],
            "charset" => "utf8"
        ]
    ]
];
