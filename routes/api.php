<?php

/**
 * API Routes
 * 
 * All routes defined here are prefixed with /api
 */

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;

// API status endpoint
Router::get('/status', function () {
    return Response::json([
        'status' => 'ok',
        'framework' => 'DIS Framework',
        'php_version' => PHP_VERSION,
        'timestamp' => date('c'),
    ]);
})->name('api.status');

// Example API routes (uncomment to use)
// Router::get('/users', 'Api\UserController@index');
// Router::get('/users/{id}', 'Api\UserController@show');
// Router::post('/users', 'Api\UserController@store');
// Router::put('/users/{id}', 'Api\UserController@update');
// Router::delete('/users/{id}', 'Api\UserController@destroy');
