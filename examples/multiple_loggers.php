<?php
require_once(realpath(__DIR__.'/../vendor/autoload.php'));

use Cascade\Cascade;

$loggerConfigFile = realpath(__DIR__.'/logger_config.yml');

// you can use json too!
// $loggerConfigFile = realpath(__DIR__.'/logger_config.json');

Cascade::fileConfig($loggerConfigFile);
Cascade::getLogger('loggerA')->info('Well, that works!');
Cascade::getLogger('loggerB')->error('Maybe not...');

Cascade::getLogger('loggerA.child')->info('Will this propogate?');
Cascade::getLogger('loggerA.childNotInConfig')->info('Will this inherit?');


// This should log into 2 different log files depending on the level: 'example_info.log' and 'example_error.log'