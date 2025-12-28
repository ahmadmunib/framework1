<?php
// In public/index.php
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/framework/Core/Autoloader.php';

use Framework\Core\Autoloader;

Autoloader::register();
Autoloader::addNamespace('Framework\\', BASE_PATH . '/framework/');
Autoloader::addNamespace('App\\', BASE_PATH . '/app/');

// Now classes auto-load:
$request = new Framework\Http\Request();  // Loads framework/Http/Request.php
$user = new App\Models\User();             // Loads app/Models/User.php