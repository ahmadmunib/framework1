<?php

declare(strict_types=1);

/**
 * Request Unit Tests
 * 
 * Run: php tests/Unit/RequestTest.php
 */

require_once __DIR__ . '/../../framework/Core/Autoloader.php';

use Framework\Core\Autoloader;
use Framework\Http\Request;

Autoloader::register();
Autoloader::addNamespace('Framework\\', dirname(__DIR__, 2) . '/framework/');

class RequestTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Running Request Tests...\n";
        echo str_repeat('-', 40) . "\n";

        $this->testCreate();
        $this->testInput();
        $this->testQueryAndPost();
        $this->testHasAndFilled();
        $this->testOnlyAndExcept();
        $this->testMethod();
        $this->testMethodSpoofing();
        $this->testPath();
        $this->testUrl();
        $this->testHeaders();
        $this->testJson();
        $this->testAjax();
        $this->testRouteParams();

        echo str_repeat('-', 40) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
    }

    private function testCreate(): void
    {
        $request = Request::create('/users', 'GET', ['page' => '1']);
        
        $this->assertEquals('/users', $request->path(), 'Path should be /users');
        $this->assertEquals('GET', $request->method(), 'Method should be GET');
        $this->assertEquals('1', $request->query('page'), 'Query param should be set');
        
        echo "✓ testCreate\n";
    }

    private function testInput(): void
    {
        $request = Request::create('/test', 'POST', ['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertEquals('John', $request->input('name'), 'Should get POST input');
        $this->assertEquals('john@example.com', $request->input('email'), 'Should get email');
        $this->assertEquals('default', $request->input('missing', 'default'), 'Should return default');
        $this->assertNull($request->input('missing'), 'Should return null for missing without default');
        
        echo "✓ testInput\n";
    }

    private function testQueryAndPost(): void
    {
        // GET request
        $getRequest = Request::create('/test?foo=bar', 'GET', ['page' => '2']);
        $this->assertEquals('2', $getRequest->query('page'), 'GET params should be in query');
        
        // POST request
        $postRequest = Request::create('/test', 'POST', ['name' => 'Jane']);
        $this->assertEquals('Jane', $postRequest->post('name'), 'POST params should be in post');
        $this->assertNull($postRequest->query('name'), 'POST params should not be in query');
        
        echo "✓ testQueryAndPost\n";
    }

    private function testHasAndFilled(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'empty' => '',
            'zero' => '0',
        ]);
        
        $this->assertTrue($request->has('name'), 'Should have name');
        $this->assertTrue($request->has('empty'), 'Should have empty (key exists)');
        $this->assertFalse($request->has('missing'), 'Should not have missing');
        $this->assertTrue($request->has(['name', 'empty']), 'Should have multiple keys');
        $this->assertFalse($request->has(['name', 'missing']), 'Should fail if any key missing');
        
        $this->assertTrue($request->filled('name'), 'name should be filled');
        $this->assertFalse($request->filled('empty'), 'empty should not be filled');
        $this->assertTrue($request->filled('zero'), 'zero should be filled');
        
        echo "✓ testHasAndFilled\n";
    }

    private function testOnlyAndExcept(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);
        
        $only = $request->only(['name', 'email']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $only, 'Only should filter');
        
        $except = $request->except(['password']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $except, 'Except should exclude');
        
        echo "✓ testOnlyAndExcept\n";
    }

    private function testMethod(): void
    {
        $get = Request::create('/test', 'GET');
        $this->assertEquals('GET', $get->method(), 'Should be GET');
        $this->assertTrue($get->isGet(), 'isGet should be true');
        $this->assertFalse($get->isPost(), 'isPost should be false');
        
        $post = Request::create('/test', 'POST');
        $this->assertEquals('POST', $post->method(), 'Should be POST');
        $this->assertTrue($post->isPost(), 'isPost should be true');
        $this->assertTrue($post->isMethod('POST'), 'isMethod POST should be true');
        
        echo "✓ testMethod\n";
    }

    private function testMethodSpoofing(): void
    {
        // Spoof via _method field
        $put = Request::create('/test', 'POST', ['_method' => 'PUT']);
        $this->assertEquals('PUT', $put->method(), 'Should be PUT via _method');
        
        $delete = Request::create('/test', 'POST', ['_method' => 'DELETE']);
        $this->assertEquals('DELETE', $delete->method(), 'Should be DELETE via _method');
        
        echo "✓ testMethodSpoofing\n";
    }

    private function testPath(): void
    {
        $request = Request::create('/users/123/posts');
        $this->assertEquals('/users/123/posts', $request->path(), 'Path should match');
        
        $root = Request::create('/');
        $this->assertEquals('/', $root->path(), 'Root path should be /');
        
        echo "✓ testPath\n";
    }

    private function testUrl(): void
    {
        $request = Request::create('/users', 'GET', [], [], [], [
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
        ]);
        
        $this->assertEquals('https://example.com/users', $request->url(), 'URL should include scheme and host');
        $this->assertTrue($request->secure(), 'Should be secure');
        $this->assertEquals('https', $request->scheme(), 'Scheme should be https');
        
        echo "✓ testUrl\n";
    }

    private function testHeaders(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CUSTOM_HEADER' => 'custom-value',
            'CONTENT_TYPE' => 'application/json',
        ]);
        
        $this->assertEquals('application/json', $request->header('accept'), 'Should get accept header');
        $this->assertEquals('custom-value', $request->header('x-custom-header'), 'Should get custom header');
        $this->assertEquals('application/json', $request->header('content-type'), 'Should get content-type');
        $this->assertEquals('default', $request->header('missing', 'default'), 'Should return default');
        
        echo "✓ testHeaders\n";
    }

    private function testJson(): void
    {
        $jsonContent = json_encode(['name' => 'John', 'age' => 30]);
        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $jsonContent);
        
        $this->assertTrue($request->isJson(), 'Should detect JSON content');
        $this->assertEquals('John', $request->json('name'), 'Should get JSON field');
        $this->assertEquals(30, $request->json('age'), 'Should get JSON number');
        $this->assertEquals(['name' => 'John', 'age' => 30], $request->json(), 'Should get all JSON');
        
        echo "✓ testJson\n";
    }

    private function testAjax(): void
    {
        $ajax = Request::create('/test', 'GET', [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        $this->assertTrue($ajax->ajax(), 'Should detect AJAX');
        
        $normal = Request::create('/test', 'GET');
        $this->assertFalse($normal->ajax(), 'Should not be AJAX');
        
        echo "✓ testAjax\n";
    }

    private function testRouteParams(): void
    {
        $request = Request::create('/users/123');
        $request->setRouteParams(['id' => '123', 'action' => 'show']);
        
        $this->assertEquals('123', $request->route('id'), 'Should get route param');
        $this->assertEquals('show', $request->route('action'), 'Should get action param');
        $this->assertNull($request->route('missing'), 'Should return null for missing');
        $this->assertEquals(['id' => '123', 'action' => 'show'], $request->route(), 'Should get all params');
        
        echo "✓ testRouteParams\n";
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

    private function assertNull($value, string $message): void
    {
        $this->assertTrue($value === null, $message);
    }
}

// Run the tests
$test = new RequestTest();
$test->run();
