<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

/**
 * Scaffolds route files (web.php and api.php) with starter content.
 */
class MakeRoutesCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:routes';
    }

    public function getDescription(): string
    {
        return 'Create routes/web.php and routes/api.php with starter content';
    }

    /**
     * @param string[] $arguments
     */
    public function handle(array $arguments): int
    {
        $baseDir = base_path('routes');
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            fwrite(STDERR, "Unable to create routes directory: {$baseDir}\n");
            return 1;
        }

        $webPath = $baseDir . DIRECTORY_SEPARATOR . 'web.php';
        $apiPath = $baseDir . DIRECTORY_SEPARATOR . 'api.php';

        $webResult = $this->ensureWebRoutes($webPath);
        $apiResult = $this->ensureApiRoutes($apiPath);

        if ($webResult && $apiResult) {
            fwrite(STDOUT, "Routes scaffolded successfully.\n");
            return 0;
        }

        return 1;
    }

    protected function ensureWebRoutes(string $path): bool
    {
        if (file_exists($path)) {
            fwrite(STDOUT, "web.php already exists, leaving unchanged.\n");
            return true;
        }

        $content = <<<'PHP'
<?php

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

PHP;

        if (file_put_contents($path, $content) === false) {
            fwrite(STDERR, "Failed to write {$path}\n");
            return false;
        }

        fwrite(STDOUT, "Created {$path}\n");
        return true;
    }

    protected function ensureApiRoutes(string $path): bool
    {
        if (file_exists($path)) {
            fwrite(STDOUT, "api.php already exists, leaving unchanged.\n");
            return true;
        }

        $content = <<<'PHP'
<?php

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

PHP;

        if (file_put_contents($path, $content) === false) {
            fwrite(STDERR, "Failed to write {$path}\n");
            return false;
        }

        fwrite(STDOUT, "Created {$path}\n");
        return true;
    }
}
