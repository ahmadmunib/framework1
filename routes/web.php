<?php

/**
 * Web Routes
 * 
 * Define your application routes here.
 */

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;

// Home route
Router::get('/', function () {
    return new Response('
        <!DOCTYPE html>
        <html>
        <head>
            <title>DIS Framework</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                       display: flex; align-items: center; justify-content: center; min-height: 100vh;
                       background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; }
                .container { text-align: center; color: white; }
                h1 { font-size: 3rem; margin-bottom: 0.5rem; }
                p { font-size: 1.2rem; opacity: 0.9; }
                .version { margin-top: 2rem; padding: 10px 20px; background: rgba(255,255,255,0.2); 
                           border-radius: 20px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🚀 DIS Framework</h1>
                <p>Your custom PHP framework is running!</p>
                <div class="version">PHP ' . PHP_VERSION . '</div>
            </div>
        </body>
        </html>
    ');
})->name('home');

// Example routes (uncomment to use)
// Router::get('/users', 'UserController@index')->name('users.index');
// Router::get('/users/{id}', 'UserController@show')->name('users.show');
// Router::post('/users', 'UserController@store')->name('users.store');
