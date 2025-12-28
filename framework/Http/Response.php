<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * HTTP Response Handler
 * 
 * Encapsulates HTTP response data including status, headers, and content.
 * 
 * @example
 * $response = new Response('Hello World', 200);
 * $response->header('Content-Type', 'text/plain')->send();
 */
class Response
{
    /**
     * Response content
     */
    protected mixed $content;

    /**
     * HTTP status code
     */
    protected int $statusCode;

    /**
     * Response headers
     */
    protected array $headers = [];

    /**
     * Cookies to set
     */
    protected array $cookies = [];

    /**
     * HTTP status texts
     */
    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        410 => 'Gone',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Create a new Response instance
     */
    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->statusCode = $status;
        
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
    }

    /**
     * Create a new Response instance (static factory)
     */
    public static function make(mixed $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Set response content
     */
    public function setContent(mixed $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header
     */
    public function header(string $name, string $value, bool $replace = true): static
    {
        $name = strtolower($name);
        
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }
        
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Get a response header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set a cookie
     */
    public function cookie(
        string $name,
        string $value = '',
        int $minutes = 0,
        ?string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): static {
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $minutes > 0 ? time() + ($minutes * 60) : 0,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];
        
        return $this;
    }

    /**
     * Remove a cookie
     */
    public function withoutCookie(string $name, ?string $path = '/', ?string $domain = null): static
    {
        return $this->cookie($name, '', -2628000, $path, $domain);
    }

    /**
     * Create a JSON response
     */
    public static function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): static
    {
        $json = json_encode($data, $options | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }
        
        $headers['content-type'] = 'application/json';
        
        return new static($json, $status, $headers);
    }

    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers['location'] = $url;
        return new static('', $status, $headers);
    }

    /**
     * Create a "no content" response
     */
    public static function noContent(int $status = 204): static
    {
        return new static('', $status);
    }

    /**
     * Create a file download response
     */
    public static function download(string $path, ?string $name = null, array $headers = []): static
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $name = $name ?? basename($path);
        $content = file_get_contents($path);
        
        $headers = array_merge([
            'content-type' => mime_content_type($path) ?: 'application/octet-stream',
            'content-disposition' => 'attachment; filename="' . $name . '"',
            'content-length' => (string) filesize($path),
        ], $headers);
        
        return new static($content, 200, $headers);
    }

    /**
     * Create a streamed file response (inline display)
     */
    public static function file(string $path, array $headers = []): static
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $content = file_get_contents($path);
        
        $headers = array_merge([
            'content-type' => mime_content_type($path) ?: 'application/octet-stream',
            'content-length' => (string) filesize($path),
        ], $headers);
        
        return new static($content, 200, $headers);
    }

    /**
     * Send the response to the client
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();
        
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        return $this;
    }

    /**
     * Send response headers
     */
    protected function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }
        
        // Send status line
        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}", true, $this->statusCode);
        
        // Send headers
        foreach ($this->headers as $name => $value) {
            // Convert header name to Title-Case
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            header("{$name}: {$value}", true);
        }
        
        // Send cookies
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'] ?? '',
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => $cookie['samesite'],
                ]
            );
        }
        
        return $this;
    }

    /**
     * Send response content
     */
    protected function sendContent(): static
    {
        echo $this->content;
        return $this;
    }

    /**
     * Check if response is a redirect
     */
    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    /**
     * Check if response is successful (2xx)
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is OK (200)
     */
    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    /**
     * Check if response is not found (404)
     */
    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    /**
     * Convert response to string
     */
    public function __toString(): string
    {
        return (string) $this->content;
    }
}
