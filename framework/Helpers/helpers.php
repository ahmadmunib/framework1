<?php

declare(strict_types=1);

/**
 * Framework Helper Functions
 * 
 * Global helper functions for common operations.
 * These provide a convenient way to access framework features.
 */

use Framework\Core\Config;

if (!function_exists('config')) {
    /**
     * Get or set configuration values
     * 
     * @param string|array|null $key Key in dot notation, array of key-value pairs, or null
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value, or Config instance if no key provided
     * 
     * @example
     * config('app.name');                        // Get value
     * config('app.debug', false);                // Get with default
     * config(['app.debug' => false]);            // Set value(s)
     */
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        // If no key, return all config
        if (is_null($key)) {
            return Config::all();
        }

        // If array, set multiple values
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Config::set($k, $v);
            }
            return null;
        }

        // Otherwise, get the value
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable value
     * 
     * @param string $key Environment variable name
     * @param mixed $default Default value if not set
     * @return mixed
     * 
     * @example
     * env('APP_DEBUG', false);
     * env('DB_HOST', 'localhost');
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        // Convert string booleans and null
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the application base path
     * 
     * @param string $path Optional path to append
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        return $basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration directory path
     * 
     * @param string $path Optional path to append
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage directory path
     * 
     * @param string $path Optional path to append
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the public directory path
     * 
     * @param string $path Optional path to append
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the application directory path
     * 
     * @param string $path Optional path to append
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('dd')) {
    /**
     * Dump variables and die
     * 
     * @param mixed ...$vars Variables to dump
     * @return never
     */
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables without dying
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('value')) {
    /**
     * Return the value or execute closure
     * 
     * @param mixed $value Value or closure
     * @param mixed ...$args Arguments to pass to closure
     * @return mixed
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
