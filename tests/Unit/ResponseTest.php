<?php

declare(strict_types=1);

/**
 * Response Unit Tests
 * 
 * Run: php tests/Unit/ResponseTest.php
 */

require_once __DIR__ . '/../../framework/Core/Autoloader.php';

use Framework\Core\Autoloader;
use Framework\Http\Response;

Autoloader::register();
Autoloader::addNamespace('Framework\\', dirname(__DIR__, 2) . '/framework/');

class ResponseTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Running Response Tests...\n";
        echo str_repeat('-', 40) . "\n";

        $this->testConstruct();
        $this->testMake();
        $this->testContent();
        $this->testStatusCode();
        $this->testHeaders();
        $this->testJson();
        $this->testRedirect();
        $this->testNoContent();
        $this->testStatusChecks();
        $this->testCookies();

        echo str_repeat('-', 40) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
    }

    private function testConstruct(): void
    {
        $response = new Response('Hello', 201, ['X-Custom' => 'value']);
        
        $this->assertEquals('Hello', $response->getContent(), 'Content should match');
        $this->assertEquals(201, $response->getStatusCode(), 'Status should be 201');
        $this->assertEquals('value', $response->getHeader('x-custom'), 'Custom header should exist');
        
        echo "✓ testConstruct\n";
    }

    private function testMake(): void
    {
        $response = Response::make('Content', 200);
        
        $this->assertEquals('Content', $response->getContent(), 'Content should match');
        $this->assertEquals(200, $response->getStatusCode(), 'Status should be 200');
        
        echo "✓ testMake\n";
    }

    private function testContent(): void
    {
        $response = new Response();
        
        $response->setContent('New content');
        $this->assertEquals('New content', $response->getContent(), 'Content should be updated');
        
        $response->setContent(['array' => 'data']);
        $this->assertEquals(['array' => 'data'], $response->getContent(), 'Should accept array');
        
        echo "✓ testContent\n";
    }

    private function testStatusCode(): void
    {
        $response = new Response();
        
        $response->setStatusCode(404);
        $this->assertEquals(404, $response->getStatusCode(), 'Status should be 404');
        
        $response->setStatusCode(500);
        $this->assertEquals(500, $response->getStatusCode(), 'Status should be 500');
        
        echo "✓ testStatusCode\n";
    }

    private function testHeaders(): void
    {
        $response = new Response();
        
        $response->header('Content-Type', 'application/json');
        $this->assertEquals('application/json', $response->getHeader('content-type'), 'Header should be set');
        
        $response->header('X-Custom', 'value1');
        $response->header('X-Custom', 'value2', false); // Don't replace
        $this->assertEquals('value1', $response->getHeader('x-custom'), 'Should not replace when false');
        
        $response->header('X-Custom', 'value3', true); // Replace
        $this->assertEquals('value3', $response->getHeader('x-custom'), 'Should replace when true');
        
        $response->withHeaders([
            'X-Header1' => 'val1',
            'X-Header2' => 'val2',
        ]);
        $this->assertEquals('val1', $response->getHeader('x-header1'), 'withHeaders should set multiple');
        $this->assertEquals('val2', $response->getHeader('x-header2'), 'withHeaders should set multiple');
        
        echo "✓ testHeaders\n";
    }

    private function testJson(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $response = Response::json($data, 201);
        
        $this->assertEquals(json_encode($data), $response->getContent(), 'JSON content should match');
        $this->assertEquals(201, $response->getStatusCode(), 'Status should be 201');
        $this->assertEquals('application/json', $response->getHeader('content-type'), 'Content-Type should be JSON');
        
        // Test with nested data
        $nested = ['users' => [['id' => 1], ['id' => 2]]];
        $response2 = Response::json($nested);
        $this->assertEquals(json_encode($nested), $response2->getContent(), 'Nested JSON should work');
        
        echo "✓ testJson\n";
    }

    private function testRedirect(): void
    {
        $response = Response::redirect('/dashboard');
        
        $this->assertEquals(302, $response->getStatusCode(), 'Default redirect status should be 302');
        $this->assertEquals('/dashboard', $response->getHeader('location'), 'Location header should be set');
        $this->assertTrue($response->isRedirect(), 'isRedirect should be true');
        
        $response301 = Response::redirect('/new-url', 301);
        $this->assertEquals(301, $response301->getStatusCode(), 'Should accept custom status');
        
        echo "✓ testRedirect\n";
    }

    private function testNoContent(): void
    {
        $response = Response::noContent();
        
        $this->assertEquals(204, $response->getStatusCode(), 'Status should be 204');
        $this->assertEquals('', $response->getContent(), 'Content should be empty');
        
        echo "✓ testNoContent\n";
    }

    private function testStatusChecks(): void
    {
        $ok = new Response('', 200);
        $this->assertTrue($ok->isOk(), '200 should be OK');
        $this->assertTrue($ok->isSuccessful(), '200 should be successful');
        
        $created = new Response('', 201);
        $this->assertTrue($created->isSuccessful(), '201 should be successful');
        $this->assertFalse($created->isOk(), '201 should not be isOk');
        
        $notFound = new Response('', 404);
        $this->assertTrue($notFound->isNotFound(), '404 should be not found');
        $this->assertTrue($notFound->isClientError(), '404 should be client error');
        $this->assertFalse($notFound->isServerError(), '404 should not be server error');
        
        $serverError = new Response('', 500);
        $this->assertTrue($serverError->isServerError(), '500 should be server error');
        $this->assertFalse($serverError->isClientError(), '500 should not be client error');
        
        $redirect = new Response('', 301);
        $this->assertTrue($redirect->isRedirect(), '301 should be redirect');
        
        echo "✓ testStatusChecks\n";
    }

    private function testCookies(): void
    {
        $response = new Response();
        
        // Method chaining
        $result = $response->cookie('session', 'abc123', 60);
        $this->assertTrue($result === $response, 'cookie() should return self');
        
        // Without cookie
        $result = $response->withoutCookie('old_cookie');
        $this->assertTrue($result === $response, 'withoutCookie() should return self');
        
        echo "✓ testCookies\n";
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
}

// Run the tests
$test = new ResponseTest();
$test->run();
