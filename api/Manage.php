<?php

use Core\Command\DefaultManager;
use Core\Command\Exception\InvalidParamException;
use Engine\Debugger\Traceback;

date_default_timezone_set('Europe/Moscow');
ini_set('memory_limit','128M');
set_time_limit(0);

require_once 'include.php';

// Configure argument parser
$short_commands = 'c:';
$long_commands = [
    'name:',
    'prefix:',
    'job:',
    'db:',
    'params:',
];
$arguments = getopt($short_commands, $long_commands);

// Pass command to DefaultManager and execute
try {
    DefaultManager::command($arguments);
    exit();
} catch (InvalidParamException $e) {
    $response = "Param `{$e->getMessage()}` is` required";
} catch (Exception $e) {
    $response = Traceback::stringifyException($e);
}

echo $response . "\n";
exit();
