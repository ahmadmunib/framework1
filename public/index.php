<?php

declare(strict_types=1);

/**
 * DIS Framework - Front Controller
 * 
 * All requests are routed through this file.
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Register autoloader
require_once BASE_PATH . '/framework/Core/Autoloader.php';

use Framework\Core\Autoloader;
use Framework\Core\Config;
use Framework\Core\ErrorHandler;
use Framework\Core\ExceptionHandler;
use Framework\Http\Request;
use Framework\Routing\Router;

// Register autoloader
Autoloader::register();
Autoloader::addNamespace('Framework\\', BASE_PATH . '/framework/');
Autoloader::addNamespace('App\\', BASE_PATH . '/app/');

// Load helper functions
require_once BASE_PATH . '/framework/Helpers/helpers.php';

// Load configuration
Config::load(BASE_PATH . '/config');

// Register error handlers
ErrorHandler::register();
ExceptionHandler::register();

// Set timezone
date_default_timezone_set(Config::get('app.timezone', 'UTC'));

// Load routes
$routesFile = BASE_PATH . '/routes/web.php';
if (file_exists($routesFile)) {
    require_once $routesFile;
}

// Load API routes
$apiRoutesFile = BASE_PATH . '/routes/api.php';
if (file_exists($apiRoutesFile)) {
    Router::group(['prefix' => 'api'], function () use ($apiRoutesFile) {
        require_once $apiRoutesFile;
    });
}

// Capture request and dispatch
$request = Request::capture();
$response = Router::dispatch($request);

// Send response
$response->send();
