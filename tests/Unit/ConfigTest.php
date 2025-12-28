<?php

declare(strict_types=1);

/**
 * Config Unit Tests
 * 
 * Run: php tests/Unit/ConfigTest.php
 */

require_once __DIR__ . '/../../framework/Core/Autoloader.php';

use Framework\Core\Autoloader;
use Framework\Core\Config;

Autoloader::register();
Autoloader::addNamespace('Framework\\', dirname(__DIR__, 2) . '/framework/');

// Load helpers
require_once dirname(__DIR__, 2) . '/framework/Helpers/helpers.php';

class ConfigTest
{
    private int $passed = 0;
    private int $failed = 0;
    private string $configPath;

    public function __construct()
    {
        $this->configPath = dirname(__DIR__, 2) . '/config';
    }

    public function run(): void
    {
        echo "Running Config Tests...\n";
        echo str_repeat('-', 40) . "\n";

        $this->testLoad();
        $this->testGet();
        $this->testGetWithDotNotation();
        $this->testGetWithDefault();
        $this->testSet();
        $this->testSetWithDotNotation();
        $this->testHas();
        $this->testForget();
        $this->testPushAndPrepend();
        $this->testHelperFunction();
        $this->testEnvHelper();

        echo str_repeat('-', 40) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
    }

    private function testLoad(): void
    {
        Config::clear();
        
        Config::load($this->configPath);
        
        $this->assertTrue(Config::isLoaded(), 'Config should be loaded');
        $this->assertTrue(Config::has('app'), 'Should have app config');
        $this->assertTrue(Config::has('database'), 'Should have database config');
        
        echo "✓ testLoad\n";
    }

    private function testGet(): void
    {
        Config::clear();
        Config::load($this->configPath);
        
        $app = Config::get('app');
        $this->assertTrue(is_array($app), 'app config should be array');
        $this->assertEquals('DIS Framework', $app['name'], 'app.name should match');
        
        echo "✓ testGet\n";
    }

    private function testGetWithDotNotation(): void
    {
        Config::clear();
        Config::load($this->configPath);
        
        $name = Config::get('app.name');
        $this->assertEquals('DIS Framework', $name, 'Dot notation should work');
        
        $host = Config::get('database.connections.mysql.host');
        $this->assertEquals('localhost', $host, 'Deep dot notation should work');
        
        $charset = Config::get('database.connections.mysql.charset');
        $this->assertEquals('utf8mb4', $charset, 'Should get nested value');
        
        echo "✓ testGetWithDotNotation\n";
    }

    private function testGetWithDefault(): void
    {
        Config::clear();
        Config::load($this->configPath);
        
        $missing = Config::get('nonexistent.key', 'default_value');
        $this->assertEquals('default_value', $missing, 'Should return default for missing key');
        
        $deepMissing = Config::get('app.deeply.nested.missing', 'fallback');
        $this->assertEquals('fallback', $deepMissing, 'Should return default for deep missing key');
        
        echo "✓ testGetWithDefault\n";
    }

    private function testSet(): void
    {
        Config::clear();
        
        Config::set('custom', 'value');
        $this->assertEquals('value', Config::get('custom'), 'Should set simple value');
        
        Config::set('custom', ['nested' => 'data']);
        $this->assertEquals(['nested' => 'data'], Config::get('custom'), 'Should set array value');
        
        echo "✓ testSet\n";
    }

    private function testSetWithDotNotation(): void
    {
        Config::clear();
        
        Config::set('app.custom.setting', 'test_value');
        $this->assertEquals('test_value', Config::get('app.custom.setting'), 'Should set with dot notation');
        
        Config::set('deep.nested.path.value', 123);
        $this->assertEquals(123, Config::get('deep.nested.path.value'), 'Should create deep nested structure');
        
        echo "✓ testSetWithDotNotation\n";
    }

    private function testHas(): void
    {
        Config::clear();
        Config::load($this->configPath);
        
        $this->assertTrue(Config::has('app'), 'Should have app');
        $this->assertTrue(Config::has('app.name'), 'Should have app.name');
        $this->assertTrue(Config::has('database.connections.mysql'), 'Should have nested key');
        $this->assertFalse(Config::has('nonexistent'), 'Should not have nonexistent');
        $this->assertFalse(Config::has('app.nonexistent'), 'Should not have app.nonexistent');
        
        echo "✓ testHas\n";
    }

    private function testForget(): void
    {
        Config::clear();
        Config::set('remove.this.key', 'value');
        
        $this->assertTrue(Config::has('remove.this.key'), 'Key should exist before forget');
        
        Config::forget('remove.this.key');
        $this->assertFalse(Config::has('remove.this.key'), 'Key should not exist after forget');
        
        echo "✓ testForget\n";
    }

    private function testPushAndPrepend(): void
    {
        Config::clear();
        Config::set('list', ['b', 'c']);
        
        Config::push('list', 'd');
        $this->assertEquals(['b', 'c', 'd'], Config::get('list'), 'Push should add to end');
        
        Config::prepend('list', 'a');
        $this->assertEquals(['a', 'b', 'c', 'd'], Config::get('list'), 'Prepend should add to beginning');
        
        echo "✓ testPushAndPrepend\n";
    }

    private function testHelperFunction(): void
    {
        Config::clear();
        Config::load($this->configPath);
        
        // Get value
        $name = config('app.name');
        $this->assertEquals('DIS Framework', $name, 'config() helper should get value');
        
        // Get with default
        $missing = config('missing.key', 'default');
        $this->assertEquals('default', $missing, 'config() helper should return default');
        
        // Set values
        config(['test.key' => 'test_value']);
        $this->assertEquals('test_value', config('test.key'), 'config() helper should set value');
        
        // Get all
        $all = config();
        $this->assertTrue(is_array($all), 'config() with no args should return array');
        
        echo "✓ testHelperFunction\n";
    }

    private function testEnvHelper(): void
    {
        // Set test environment variable
        putenv('TEST_VAR=test_value');
        putenv('TEST_BOOL_TRUE=true');
        putenv('TEST_BOOL_FALSE=false');
        
        $this->assertEquals('test_value', env('TEST_VAR'), 'env() should get environment variable');
        $this->assertEquals('default', env('NONEXISTENT_VAR', 'default'), 'env() should return default');
        $this->assertTrue(env('TEST_BOOL_TRUE') === true, 'env() should convert true string');
        $this->assertTrue(env('TEST_BOOL_FALSE') === false, 'env() should convert false string');
        
        // Clean up
        putenv('TEST_VAR');
        putenv('TEST_BOOL_TRUE');
        putenv('TEST_BOOL_FALSE');
        
        echo "✓ testEnvHelper\n";
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
$test = new ConfigTest();
$test->run();
