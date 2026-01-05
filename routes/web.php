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
                pre {
                    background: rgba(0,0,0,0.25);
                    color: #e4e7ff;
                    padding: 16px 20px;
                    border-radius: 12px;
                    font-size: 14px;
                    line-height: 1.4;
                    display: inline-block;
                    margin-top: 20px;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🚀 DIS Framework</h1>
                <p>Your custom PHP framework is running!</p>
                <div class="version">PHP ' . PHP_VERSION . '</div>
                <pre> ____  ___ ____     ______                                      
|  _ \\|_ _|  _ \\   |  ____|                                     
| | | || || |_) |  | |__ _ __ __ _ _ __ ___   _____      ___ __  
| |_| || ||  __/   |  __| \'__/ _` | \'_ ` _ \\ / _ \\ \\ /\\ / / \'__| 
|____/|___|_|      | |  | | | (_| | | | | | |  __/\\ V  V /| |    
                   |_|  |_|  \\__,_|_| |_| |_|\\___| \\_/\\_/ |_|    
                                                                 </pre>
            </div>
        </body>
        </html>
    ');
})->name('home');

// Example routes (uncomment to use)
// Router::get('/users', 'UserController@index')->name('users.index');
// Router::get('/users/{id}', 'UserController@show')->name('users.show');
// Router::post('/users', 'UserController@store')->name('users.store');
