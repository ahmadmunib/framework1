<?php

declare(strict_types=1);

/**
 * Autoloader Unit Tests
 * 
 * Run: php tests/Unit/AutoloaderTest.php
 */

// Bootstrap - manually require the autoloader first
require_once __DIR__ . '/../../framework/Core/Autoloader.php';

use Framework\Core\Autoloader;

class AutoloaderTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "Running Autoloader Tests...\n";
        echo str_repeat('-', 40) . "\n";

        $this->testRegister();
        $this->testAddNamespace();
        $this->testFindFile();
        $this->testHasNamespace();
        $this->testRemoveNamespace();
        $this->testClear();

        echo str_repeat('-', 40) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
    }

    private function testRegister(): void
    {
        // Unregister first if already registered
        Autoloader::unregister();
        
        $this->assertFalse(Autoloader::isRegistered(), 'Should not be registered initially');
        
        Autoloader::register();
        $this->assertTrue(Autoloader::isRegistered(), 'Should be registered after register()');
        
        // Registering again should not cause issues
        Autoloader::register();
        $this->assertTrue(Autoloader::isRegistered(), 'Should still be registered');
        
        echo "✓ testRegister\n";
    }

    private function testAddNamespace(): void
    {
        Autoloader::clear();
        
        Autoloader::addNamespace('App\\', '/path/to/app/');
        $namespaces = Autoloader::getNamespaces();
        
        $this->assertTrue(isset($namespaces['App\\']), 'Namespace should be registered');
        $this->assertEquals('/path/to/app/', $namespaces['App\\'][0], 'Base dir should match');
        
        // Test multiple directories for same namespace
        Autoloader::addNamespace('App\\', '/another/path/');
        $namespaces = Autoloader::getNamespaces();
        $this->assertEquals(2, count($namespaces['App\\']), 'Should have 2 directories');
        
        // Test prepend
        Autoloader::addNamespace('App\\', '/first/path/', true);
        $namespaces = Autoloader::getNamespaces();
        $this->assertEquals('/first/path/', $namespaces['App\\'][0], 'Prepended should be first');
        
        echo "✓ testAddNamespace\n";
    }

    private function testFindFile(): void
    {
        Autoloader::clear();
        
        $baseDir = dirname(__DIR__, 2) . '/framework/';
        Autoloader::addNamespace('Framework\\', $baseDir);
        
        // Should find existing file
        $file = Autoloader::findFile('Framework\\Core\\Autoloader');
        $this->assertNotNull($file, 'Should find Autoloader file');
        $this->assertTrue(file_exists($file), 'Found file should exist');
        
        // Should return null for non-existent class
        $file = Autoloader::findFile('Framework\\NonExistent\\Class');
        $this->assertNull($file, 'Should return null for non-existent class');
        
        // Should return null for unregistered namespace
        $file = Autoloader::findFile('Unknown\\Namespace\\Class');
        $this->assertNull($file, 'Should return null for unregistered namespace');
        
        echo "✓ testFindFile\n";
    }

    private function testHasNamespace(): void
    {
        Autoloader::clear();
        
        $this->assertFalse(Autoloader::hasNamespace('Test\\'), 'Should not have namespace');
        
        Autoloader::addNamespace('Test\\', '/path/');
        $this->assertTrue(Autoloader::hasNamespace('Test\\'), 'Should have namespace');
        $this->assertTrue(Autoloader::hasNamespace('Test'), 'Should work without trailing slash');
        
        echo "✓ testHasNamespace\n";
    }

    private function testRemoveNamespace(): void
    {
        Autoloader::clear();
        
        Autoloader::addNamespace('Remove\\', '/path/');
        $this->assertTrue(Autoloader::hasNamespace('Remove\\'), 'Should have namespace');
        
        Autoloader::removeNamespace('Remove\\');
        $this->assertFalse(Autoloader::hasNamespace('Remove\\'), 'Should not have namespace after removal');
        
        echo "✓ testRemoveNamespace\n";
    }

    private function testClear(): void
    {
        Autoloader::addNamespace('One\\', '/one/');
        Autoloader::addNamespace('Two\\', '/two/');
        
        Autoloader::clear();
        $namespaces = Autoloader::getNamespaces();
        
        $this->assertEquals(0, count($namespaces), 'Should have no namespaces after clear');
        
        echo "✓ testClear\n";
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
        $this->assertTrue($expected === $actual, "$message (expected: $expected, got: $actual)");
    }

    private function assertNull($value, string $message): void
    {
        $this->assertTrue($value === null, $message);
    }

    private function assertNotNull($value, string $message): void
    {
        $this->assertTrue($value !== null, $message);
    }
}

// Run the tests
$test = new AutoloaderTest();
$test->run();
