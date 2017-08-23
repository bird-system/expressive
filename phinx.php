<?php
/**
 * User: Allan Sun (allan.sun@bricre.com)
 * Date: 14/12/2015
 * Time: 14:55
 */

require './bootstrap.php';
$configs = require APP_ROOT . '/config/config.php';
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
        "default_database" => "database",
        "default" => [
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
