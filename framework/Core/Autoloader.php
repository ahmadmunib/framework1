<?php

declare(strict_types=1);

namespace Framework\Core;

/**
 * PSR-4 Compatible Autoloader
 * 
 * Automatically loads class files based on namespace-to-directory mapping.
 * This eliminates the need for manual require/include statements.
 * 
 * @example
 * require_once 'framework/Core/Autoloader.php';
 * Autoloader::register();
 * Autoloader::addNamespace('Framework\\', __DIR__ . '/framework/');
 * Autoloader::addNamespace('App\\', __DIR__ . '/app/');
 */
class Autoloader
{
    /**
     * Registered namespace prefixes and their base directories
     * 
     * @var array<string, array<string>>
     */
    private static array $namespaces = [];

    /**
     * Whether the autoloader has been registered with SPL
     * 
     * @var bool
     */
    private static bool $registered = false;

    /**
     * Register the autoloader with SPL autoload stack
     * 
     * @param bool $prepend If true, prepend to autoload stack instead of append
     * @return void
     */
    public static function register(bool $prepend = false): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([self::class, 'loadClass'], true, $prepend);
        self::$registered = true;
    }

    /**
     * Unregister the autoloader from SPL autoload stack
     * 
     * @return void
     */
    public static function unregister(): void
    {
        if (!self::$registered) {
            return;
        }

        spl_autoload_unregister([self::class, 'loadClass']);
        self::$registered = false;
    }

    /**
     * Add a namespace prefix to base directory mapping
     * 
     * @param string $prefix The namespace prefix (e.g., 'Framework\\')
     * @param string $baseDir The base directory for this namespace
     * @param bool $prepend If true, prepend to search paths instead of append
     * @return void
     * 
     * @example
     * Autoloader::addNamespace('Framework\\', '/path/to/framework/');
     * Autoloader::addNamespace('App\\', '/path/to/app/');
     */
    public static function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        // Normalize namespace prefix - ensure trailing backslash
        $prefix = trim($prefix, '\\') . '\\';

        // Normalize base directory - ensure trailing slash
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Initialize array for this prefix if not exists
        if (!isset(self::$namespaces[$prefix])) {
            self::$namespaces[$prefix] = [];
        }

        // Add the base directory for this namespace prefix
        if ($prepend) {
            array_unshift(self::$namespaces[$prefix], $baseDir);
        } else {
            self::$namespaces[$prefix][] = $baseDir;
        }
    }

    /**
     * Load a class file for the given fully-qualified class name
     * 
     * @param string $class The fully-qualified class name
     * @return bool True if file was loaded, false otherwise
     */
    public static function loadClass(string $class): bool
    {
        // Try to find and load the mapped file
        $file = self::findFile($class);

        if ($file !== null) {
            require $file;
            return true;
        }

        return false;
    }

    /**
     * Find the file path for a given class name
     * 
     * @param string $class The fully-qualified class name
     * @return string|null The file path if found, null otherwise
     */
    public static function findFile(string $class): ?string
    {
        // Normalize class name - remove leading backslash
        $class = ltrim($class, '\\');

        // Try each registered namespace prefix
        foreach (self::$namespaces as $prefix => $directories) {
            // Check if this class uses this namespace prefix
            if (strpos($class, $prefix) !== 0) {
                continue;
            }

            // Get the relative class name (without the prefix)
            $relativeClass = substr($class, strlen($prefix));

            // Try each base directory for this prefix
            foreach ($directories as $baseDir) {
                // Convert namespace separators to directory separators
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                // If the file exists, return its path
                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Get all registered namespaces
     * 
     * @return array<string, array<string>>
     */
    public static function getNamespaces(): array
    {
        return self::$namespaces;
    }

    /**
     * Check if a namespace prefix is registered
     * 
     * @param string $prefix The namespace prefix to check
     * @return bool
     */
    public static function hasNamespace(string $prefix): bool
    {
        $prefix = trim($prefix, '\\') . '\\';
        return isset(self::$namespaces[$prefix]);
    }

    /**
     * Remove a namespace prefix
     * 
     * @param string $prefix The namespace prefix to remove
     * @return void
     */
    public static function removeNamespace(string $prefix): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        unset(self::$namespaces[$prefix]);
    }

    /**
     * Clear all registered namespaces
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$namespaces = [];
    }

    /**
     * Check if the autoloader is registered
     * 
     * @return bool
     */
    public static function isRegistered(): bool
    {
        return self::$registered;
    }
}
