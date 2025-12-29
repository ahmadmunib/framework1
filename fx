<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/framework/Core/Autoloader.php';
require_once BASE_PATH . '/framework/Helpers/helpers.php';

use Framework\Console\Application;
use Framework\Core\Autoloader;

Autoloader::register();
Autoloader::addNamespace('Framework\\', BASE_PATH . '/framework/');
Autoloader::addNamespace('App\\', BASE_PATH . '/app/');

$app = new Application();

$argv = $_SERVER['argv'] ?? [];
array_shift($argv); // remove script name

if ($argv === [] || in_array($argv[0], ['list', 'help', '--help', '-h'], true)) {
    echo $app->listCommands();
    exit(0);
}

$command = array_shift($argv);
$exitCode = $app->run($command, $argv);

exit($exitCode);
