<?php

declare(strict_types=1);

namespace Framework\Core;

use Framework\Http\Response;

/**
 * Exception Handler
 * 
 * Handles uncaught exceptions and renders appropriate error responses.
 */
class ExceptionHandler
{
    /**
     * Whether debug mode is enabled
     */
    protected static bool $debug = true;

    /**
     * Custom exception handlers
     */
    protected static array $handlers = [];

    /**
     * Register the exception handler
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);
        
        // Set debug from config if available
        if (class_exists('Framework\Core\Config')) {
            self::$debug = Config::get('app.debug', true);
        }
    }

    /**
     * Handle an exception
     */
    public static function handle(\Throwable $e): void
    {
        // Log the exception
        ErrorHandler::log($e);
        
        // Check for custom handlers
        foreach (self::$handlers as $class => $handler) {
            if ($e instanceof $class) {
                $handler($e);
                return;
            }
        }
        
        // Render the exception
        self::render($e);
    }

    /**
     * Render the exception
     */
    protected static function render(\Throwable $e): void
    {
        $statusCode = self::getStatusCode($e);
        
        // Check if expecting JSON
        $expectsJson = self::expectsJson();
        
        if ($expectsJson) {
            self::renderJson($e, $statusCode);
        } else {
            self::renderHtml($e, $statusCode);
        }
    }

    /**
     * Render JSON error response
     */
    protected static function renderJson(\Throwable $e, int $statusCode): void
    {
        $response = [
            'error' => true,
            'message' => self::$debug ? $e->getMessage() : self::getHttpMessage($statusCode),
        ];
        
        if (self::$debug) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = explode("\n", $e->getTraceAsString());
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    /**
     * Render HTML error response
     */
    protected static function renderHtml(\Throwable $e, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        
        if (self::$debug) {
            self::renderDebugView($e);
        } else {
            self::renderProductionView($statusCode);
        }
        
        exit(1);
    }

    /**
     * Render debug view with stack trace
     */
    protected static function renderDebugView(\Throwable $e): void
    {
        $class = get_class($e);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        
        // Try to get source code context
        $sourceContext = self::getSourceContext($file, $line);
        
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$class}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a2e; color: #eee; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #e74c3c; color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .header h1 { font-size: 1.5rem; margin-bottom: 10px; }
        .header .message { font-size: 1.1rem; opacity: 0.9; word-break: break-word; }
        .location { background: #16213e; padding: 15px 30px; font-family: 'Fira Code', monospace; font-size: 0.9rem; }
        .location .file { color: #00d9ff; }
        .location .line { color: #ffd700; }
        .source { background: #0f0f23; padding: 20px; overflow-x: auto; }
        .source pre { font-family: 'Fira Code', monospace; font-size: 0.85rem; }
        .source .line-number { color: #666; display: inline-block; width: 50px; user-select: none; }
        .source .highlight { background: rgba(231, 76, 60, 0.3); display: block; margin: 0 -20px; padding: 0 20px; }
        .trace { background: #16213e; padding: 20px 30px; border-radius: 0 0 8px 8px; }
        .trace h2 { font-size: 1rem; color: #888; margin-bottom: 15px; }
        .trace pre { font-family: 'Fira Code', monospace; font-size: 0.8rem; color: #aaa; white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$class}</h1>
            <div class="message">{$message}</div>
        </div>
        <div class="location">
            <span class="file">{$file}</span>:<span class="line">{$line}</span>
        </div>
        <div class="source">
            <pre>{$sourceContext}</pre>
        </div>
        <div class="trace">
            <h2>Stack Trace</h2>
            <pre>{$trace}</pre>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get source code context around the error line
     */
    protected static function getSourceContext(string $file, int $line, int $context = 10): string
    {
        if (!file_exists($file)) {
            return 'Source file not available';
        }
        
        $lines = file($file);
        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);
        
        $output = '';
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $code = htmlspecialchars($lines[$i] ?? '', ENT_QUOTES, 'UTF-8');
            $highlight = $lineNum === $line ? ' class="highlight"' : '';
            $output .= "<span{$highlight}><span class=\"line-number\">{$lineNum}</span>{$code}</span>";
        }
        
        return $output;
    }

    /**
     * Render production error view
     */
    protected static function renderProductionView(int $statusCode): void
    {
        $message = self::getHttpMessage($statusCode);
        
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$statusCode} - {$message}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-box { text-align: center; padding: 40px; }
        .error-code { font-size: 6rem; font-weight: bold; color: #e74c3c; }
        .error-message { font-size: 1.5rem; color: #333; margin-top: 10px; }
        .error-hint { color: #666; margin-top: 20px; }
        .home-link { display: inline-block; margin-top: 30px; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        .home-link:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">{$statusCode}</div>
        <div class="error-message">{$message}</div>
        <p class="error-hint">Sorry, something went wrong.</p>
        <a href="/" class="home-link">Go Home</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get HTTP status code from exception
     */
    protected static function getStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        
        return 500;
    }

    /**
     * Get HTTP status message
     */
    protected static function getHttpMessage(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Check if request expects JSON
     */
    protected static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        return str_contains($accept, '/json')
            || str_contains($accept, '+json')
            || str_contains($contentType, '/json')
            || strtolower($xhr) === 'xmlhttprequest';
    }

    /**
     * Register a custom exception handler
     */
    public static function registerHandler(string $exceptionClass, callable $handler): void
    {
        self::$handlers[$exceptionClass] = $handler;
    }

    /**
     * Set debug mode
     */
    public static function setDebug(bool $debug): void
    {
        self::$debug = $debug;
    }
}

/**
 * HTTP Exception
 * 
 * Exception with HTTP status code.
 */
class HttpException extends \RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(int $statusCode, string $message = '', array $headers = [], ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}

/**
 * Not Found Exception (404)
 */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', array $headers = [], ?\Throwable $previous = null)
    {
        parent::__construct(404, $message, $headers, $previous);
    }
}

/**
 * Unauthorized Exception (401)
 */
class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', array $headers = [], ?\Throwable $previous = null)
    {
        parent::__construct(401, $message, $headers, $previous);
    }
}

/**
 * Forbidden Exception (403)
 */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden', array $headers = [], ?\Throwable $previous = null)
    {
        parent::__construct(403, $message, $headers, $previous);
    }
}

/**
 * Validation Exception (422)
 */
class ValidationException extends HttpException
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct(422, $message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
