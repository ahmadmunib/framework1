<?php

declare(strict_types=1);

namespace Framework\Core;

/**
 * Configuration Manager
 * 
 * Manages application configuration with dot notation access.
 * Loads configuration from PHP files that return arrays.
 * 
 * @example
 * Config::load('/path/to/config');
 * $dbHost = Config::get('database.connections.mysql.host', 'localhost');
 * Config::set('app.debug', false);
 */
class Config
{
    /**
     * All loaded configuration items
     * 
     * @var array<string, mixed>
     */
    private static array $items = [];

    /**
     * Whether config has been loaded
     * 
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Load all configuration files from a directory
     * 
     * @param string $path Path to the config directory
     * @return void
     */
    public static function load(string $path): void
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . DIRECTORY_SEPARATOR . '*.php');

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $config = require $file;

            if (is_array($config)) {
                self::$items[$key] = $config;
            }
        }

        self::$loaded = true;
    }

    /**
     * Load a single configuration file
     * 
     * @param string $file Path to the config file
     * @param string|null $key Optional key to store config under
     * @return void
     */
    public static function loadFile(string $file, ?string $key = null): void
    {
        if (!file_exists($file)) {
            return;
        }

        $key = $key ?? basename($file, '.php');
        $config = require $file;

        if (is_array($config)) {
            self::$items[$key] = $config;
        }
    }

    /**
     * Get a configuration value using dot notation
     * 
     * @param string $key The key in dot notation (e.g., 'database.host')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     * 
     * @example
     * Config::get('app.name');                    // Returns 'DIS Framework'
     * Config::get('database.connections.mysql');  // Returns array
     * Config::get('missing.key', 'default');      // Returns 'default'
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Check for direct key match first
        if (array_key_exists($key, self::$items)) {
            return self::$items[$key];
        }

        // Parse dot notation
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a configuration value using dot notation
     * 
     * @param string $key The key in dot notation
     * @param mixed $value The value to set
     * @return void
     * 
     * @example
     * Config::set('app.debug', false);
     * Config::set('database.connections.mysql.host', '127.0.0.1');
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &self::$items;

        foreach ($segments as $i => $segment) {
            // If this is the last segment, set the value
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
                return;
            }

            // Create nested array if it doesn't exist
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Check if a configuration key exists
     * 
     * @param string $key The key in dot notation
     * @return bool
     */
    public static function has(string $key): bool
    {
        // Check for direct key match first
        if (array_key_exists($key, self::$items)) {
            return true;
        }

        // Parse dot notation
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get all configuration items
     * 
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Remove a configuration key
     * 
     * @param string $key The key in dot notation
     * @return void
     */
    public static function forget(string $key): void
    {
        $segments = explode('.', $key);
        $current = &self::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                unset($current[$segment]);
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Prepend a value to an array configuration
     * 
     * @param string $key The key in dot notation
     * @param mixed $value The value to prepend
     * @return void
     */
    public static function prepend(string $key, mixed $value): void
    {
        $array = self::get($key, []);

        if (is_array($array)) {
            array_unshift($array, $value);
            self::set($key, $array);
        }
    }

    /**
     * Push a value onto an array configuration
     * 
     * @param string $key The key in dot notation
     * @param mixed $value The value to push
     * @return void
     */
    public static function push(string $key, mixed $value): void
    {
        $array = self::get($key, []);

        if (is_array($array)) {
            $array[] = $value;
            self::set($key, $array);
        }
    }

    /**
     * Clear all configuration items
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$items = [];
        self::$loaded = false;
    }

    /**
     * Check if configuration has been loaded
     * 
     * @return bool
     */
    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * Merge configuration items
     * 
     * @param array<string, mixed> $items Items to merge
     * @return void
     */
    public static function merge(array $items): void
    {
        self::$items = array_merge_recursive(self::$items, $items);
    }
}
