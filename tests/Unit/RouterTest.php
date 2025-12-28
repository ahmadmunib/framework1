<?php

declare(strict_types=1);

/**
 * Router Unit Tests
 * 
 * Run: php tests/Unit/RouterTest.php
 */

require_once __DIR__ . '/../../framework/Core/Autoloader.php';

use Framework\Core\Autoloader;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;
use Framework\Routing\Route;

Autoloader::register();
Autoloader::addNamespace('Framework\\', dirname(__DIR__, 2) . '/framework/');

class RouterTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Running Router Tests...\n";
        echo str_repeat('-', 40) . "\n";

        $this->testBasicRoutes();
        $this->testRouteParameters();
        $this->testOptionalParameters();
        $this->testNamedRoutes();
        $this->testRouteGroups();
        $this->testRouteConstraints();
        $this->testClosureRoutes();
        $this->testDispatch();
        $this->testNotFound();

        echo str_repeat('-', 40) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
    }

    private function testBasicRoutes(): void
    {
        Router::clear();
        
        $get = Router::get('/users', 'UserController@index');
        $this->assertInstanceOf(Route::class, $get, 'get() should return Route');
        $this->assertEquals('/users', $get->getUri(), 'URI should match');
        $this->assertTrue(in_array('GET', $get->getMethods()), 'Should have GET method');
        
        $post = Router::post('/users', 'UserController@store');
        $this->assertTrue(in_array('POST', $post->getMethods()), 'Should have POST method');
        
        $put = Router::put('/users/{id}', 'UserController@update');
        $this->assertTrue(in_array('PUT', $put->getMethods()), 'Should have PUT method');
        
        $delete = Router::delete('/users/{id}', 'UserController@destroy');
        $this->assertTrue(in_array('DELETE', $delete->getMethods()), 'Should have DELETE method');
        
        echo "✓ testBasicRoutes\n";
    }

    private function testRouteParameters(): void
    {
        Router::clear();
        
        $route = Router::get('/users/{id}', 'UserController@show');
        
        $this->assertTrue($route->matches('/users/123'), 'Should match with parameter');
        $this->assertTrue($route->matches('/users/abc'), 'Should match alphanumeric');
        $this->assertFalse($route->matches('/users'), 'Should not match without parameter');
        $this->assertFalse($route->matches('/users/123/extra'), 'Should not match with extra segments');
        
        $params = $route->extractParameters('/users/456');
        $this->assertEquals('456', $params['id'], 'Should extract parameter');
        
        // Multiple parameters
        $route2 = Router::get('/users/{userId}/posts/{postId}', 'PostController@show');
        $this->assertTrue($route2->matches('/users/1/posts/2'), 'Should match multiple params');
        
        $params2 = $route2->extractParameters('/users/10/posts/20');
        $this->assertEquals('10', $params2['userId'], 'Should extract userId');
        $this->assertEquals('20', $params2['postId'], 'Should extract postId');
        
        echo "✓ testRouteParameters\n";
    }

    private function testOptionalParameters(): void
    {
        Router::clear();
        
        $route = Router::get('/users/{id?}', 'UserController@index');
        
        $this->assertTrue($route->matches('/users'), 'Should match without optional param');
        $this->assertTrue($route->matches('/users/123'), 'Should match with optional param');
        
        echo "✓ testOptionalParameters\n";
    }

    private function testNamedRoutes(): void
    {
        Router::clear();
        
        Router::get('/users', 'UserController@index')->name('users.index');
        Router::get('/users/{id}', 'UserController@show')->name('users.show');
        Router::get('/users/{id}/posts/{postId}', 'PostController@show')->name('posts.show');
        
        $this->assertEquals('/users', Router::route('users.index'), 'Should generate simple URL');
        $this->assertEquals('/users/5', Router::route('users.show', ['id' => 5]), 'Should generate URL with param');
        $this->assertEquals('/users/1/posts/2', Router::route('posts.show', ['id' => 1, 'postId' => 2]), 'Should generate URL with multiple params');
        
        echo "✓ testNamedRoutes\n";
    }

    private function testRouteGroups(): void
    {
        Router::clear();
        
        Router::group(['prefix' => 'admin'], function () {
            Router::get('/dashboard', 'Admin\DashboardController@index');
            Router::get('/users', 'Admin\UserController@index');
        });
        
        $routes = Router::getRoutes();
        $adminRoutes = $routes['GET'] ?? [];
        
        $dashboardFound = false;
        $usersFound = false;
        
        foreach ($adminRoutes as $route) {
            if ($route->getUri() === '/admin/dashboard') $dashboardFound = true;
            if ($route->getUri() === '/admin/users') $usersFound = true;
        }
        
        $this->assertTrue($dashboardFound, 'Should have /admin/dashboard route');
        $this->assertTrue($usersFound, 'Should have /admin/users route');
        
        // Nested groups
        Router::clear();
        Router::group(['prefix' => 'api'], function () {
            Router::group(['prefix' => 'v1'], function () {
                Router::get('/users', 'Api\V1\UserController@index');
            });
        });
        
        $routes = Router::getRoutes();
        $found = false;
        foreach ($routes['GET'] ?? [] as $route) {
            if ($route->getUri() === '/api/v1/users') $found = true;
        }
        $this->assertTrue($found, 'Should have nested prefix /api/v1/users');
        
        echo "✓ testRouteGroups\n";
    }

    private function testRouteConstraints(): void
    {
        Router::clear();
        
        $route = Router::get('/users/{id}', 'UserController@show')->whereNumber('id');
        
        $this->assertTrue($route->matches('/users/123'), 'Should match numeric');
        $this->assertFalse($route->matches('/users/abc'), 'Should not match non-numeric');
        
        $alphaRoute = Router::get('/categories/{slug}', 'CategoryController@show')->whereAlpha('slug');
        $this->assertTrue($alphaRoute->matches('/categories/electronics'), 'Should match alpha');
        $this->assertFalse($alphaRoute->matches('/categories/test123'), 'Should not match alphanumeric');
        
        echo "✓ testRouteConstraints\n";
    }

    private function testClosureRoutes(): void
    {
        Router::clear();
        
        Router::get('/test', function (Request $request) {
            return 'Hello from closure';
        });
        
        $request = Request::create('/test', 'GET');
        $response = Router::dispatch($request);
        
        $this->assertEquals('Hello from closure', $response->getContent(), 'Closure should return content');
        
        // Closure with parameters
        Router::get('/greet/{name}', function (Request $request, string $name) {
            return "Hello, {$name}!";
        });
        
        $request2 = Request::create('/greet/John', 'GET');
        $response2 = Router::dispatch($request2);
        
        $this->assertEquals('Hello, John!', $response2->getContent(), 'Closure should receive params');
        
        echo "✓ testClosureRoutes\n";
    }

    private function testDispatch(): void
    {
        Router::clear();
        
        Router::get('/json', function () {
            return ['message' => 'success'];
        });
        
        $request = Request::create('/json', 'GET');
        $response = Router::dispatch($request);
        
        $this->assertEquals('{"message":"success"}', $response->getContent(), 'Should auto-convert array to JSON');
        
        // Response object
        Router::get('/response', function () {
            return new Response('Custom response', 201);
        });
        
        $request2 = Request::create('/response', 'GET');
        $response2 = Router::dispatch($request2);
        
        $this->assertEquals(201, $response2->getStatusCode(), 'Should preserve Response status');
        
        echo "✓ testDispatch\n";
    }

    private function testNotFound(): void
    {
        Router::clear();
        
        Router::get('/exists', function () {
            return 'Found';
        });
        
        $request = Request::create('/not-exists', 'GET');
        $response = Router::dispatch($request);
        
        $this->assertEquals(404, $response->getStatusCode(), 'Should return 404 for missing route');
        
        echo "✓ testNotFound\n";
    }

    // Assertion helpers
    private function assertTrue(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
        } else {
            $this->failed++;
            echo "  FAIL: $message\n";
        }
    }

    private function assertFalse(bool $condition, string $message): void
    {
        $this->assertTrue(!$condition, $message);
    }

    private function assertEquals($expected, $actual, string $message): void
    {
        $this->assertTrue($expected === $actual, "$message (expected: " . json_encode($expected) . ", got: " . json_encode($actual) . ")");
    }

    private function assertInstanceOf(string $class, $object, string $message): void
    {
        $this->assertTrue($object instanceof $class, $message);
    }
}

// Run the tests
$test = new RouterTest();
$test->run();
