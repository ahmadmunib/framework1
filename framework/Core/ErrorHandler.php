<?php

declare(strict_types=1);

namespace Framework\Core;

/**
 * Error Handler
 * 
 * Converts PHP errors to exceptions and handles error reporting.
 */
class ErrorHandler
{
    /**
     * Whether debug mode is enabled
     */
    protected static bool $debug = true;

    /**
     * Error log path
     */
    protected static ?string $logPath = null;

    /**
     * Register the error handler
     */
    public static function register(): void
    {
        error_reporting(E_ALL);
        
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Set debug from config if available
        if (class_exists('Framework\Core\Config')) {
            self::$debug = Config::get('app.debug', true);
        }
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $exception = new \ErrorException($message, 0, $level, $file, $line);
        
        // Log the error
        self::log($exception);
        
        // In debug mode, throw as exception
        if (self::$debug) {
            throw $exception;
        }
        
        return true;
    }

    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Log an error/exception
     */
    public static function log(\Throwable $e): void
    {
        $logPath = self::$logPath ?? self::getDefaultLogPath();
        
        if (!$logPath) {
            return;
        }
        
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $entry = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get default log path
     */
    protected static function getDefaultLogPath(): ?string
    {
        if (defined('BASE_PATH')) {
            return BASE_PATH . '/storage/logs/error-' . date('Y-m-d') . '.log';
        }
        return null;
    }

    /**
     * Set debug mode
     */
    public static function setDebug(bool $debug): void
    {
        self::$debug = $debug;
    }

    /**
     * Set log path
     */
    public static function setLogPath(string $path): void
    {
        self::$logPath = $path;
    }

    /**
     * Check if in debug mode
     */
    public static function isDebug(): bool
    {
        return self::$debug;
    }
}
