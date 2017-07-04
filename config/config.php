<?php
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    // Default App module config
    BS\ConfigProvider::class,

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),

    // Load development config if it exists
    new PhpFileProvider('config/development.config.php'),
], APP_ROOT . '/data/config-cache');

return $aggregator->getMergedConfig();
