<?php

declare(strict_types=1);

namespace Framework\Http;

/**
 * HTTP Request Handler
 * 
 * Encapsulates all HTTP request data including GET, POST, headers, cookies, and files.
 * Provides a clean API for accessing request information.
 * 
 * @example
 * $request = Request::capture();
 * $name = $request->input('name');
 * $email = $request->input('email', 'default@example.com');
 */
class Request
{
    /**
     * GET parameters
     */
    protected array $query = [];

    /**
     * POST parameters
     */
    protected array $post = [];

    /**
     * Request headers
     */
    protected array $headers = [];

    /**
     * Cookies
     */
    protected array $cookies = [];

    /**
     * Uploaded files
     */
    protected array $files = [];

    /**
     * Server parameters
     */
    protected array $server = [];

    /**
     * Raw request body
     */
    protected ?string $content = null;

    /**
     * Decoded JSON body
     */
    protected ?array $json = null;

    /**
     * Route parameters (populated by router)
     */
    protected array $routeParams = [];

    /**
     * Create a new Request instance
     */
    public function __construct(
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $this->normalizeFiles($files);
        $this->server = $server;
        $this->content = $content;
        $this->headers = $this->parseHeaders($server);
    }

    /**
     * Create a Request from PHP globals
     */
    public static function capture(): static
    {
        return new static(
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            $_SERVER,
            file_get_contents('php://input') ?: null
        );
    }

    /**
     * Create a Request for testing
     */
    public static function create(
        string $uri,
        string $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): static {
        $server = array_merge([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => $uri,
            'REQUEST_METHOD' => strtoupper($method),
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => $uri,
            'QUERY_STRING' => '',
        ], $server);

        if (in_array(strtoupper($method), ['GET', 'HEAD'])) {
            $query = $parameters;
            $post = [];
        } else {
            $query = [];
            $post = $parameters;
        }

        return new static($query, $post, $cookies, $files, $server, $content);
    }

    /**
     * Get an input value from GET or POST (POST takes precedence)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $this->json()[$key] ?? $default;
    }

    /**
     * Get all input data (merged GET, POST, and JSON)
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json() ?? []);
    }

    /**
     * Get only specified keys from input
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Get all input except specified keys
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Check if input has a key
     */
    public function has(string|array $keys): bool
    {
        $keys = (array) $keys;
        $all = $this->all();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $all)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if input has a non-empty value
     */
    public function filled(string|array $keys): bool
    {
        $keys = (array) $keys;

        foreach ($keys as $key) {
            $value = $this->input($key);
            if ($value === null || $value === '' || $value === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a query string parameter
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a POST parameter
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Get JSON decoded body
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->json === null && $this->content) {
            $this->json = json_decode($this->content, true) ?? [];
        }

        if ($key === null) {
            return $this->json ?? [];
        }

        return $this->json[$key] ?? $default;
    }

    /**
     * Get a request header
     */
    public function header(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->headers;
        }

        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get a cookie value
     */
    public function cookie(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get an uploaded file
     */
    public function file(string $key): ?UploadedFile
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if a file was uploaded
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file !== null && $file->isValid();
    }

    /**
     * Get all uploaded files
     */
    public function allFiles(): array
    {
        return $this->files;
    }

    /**
     * Get the request method
     */
    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Check for method override (for PUT, PATCH, DELETE via POST)
        if ($method === 'POST') {
            $override = $this->header('x-http-method-override')
                ?? $this->input('_method');
            
            if ($override) {
                $method = strtoupper($override);
            }
        }

        return $method;
    }

    /**
     * Check if request method matches
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is AJAX
     */
    public function ajax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, '/json') || str_contains($accept, '+json');
    }

    /**
     * Check if request content is JSON
     */
    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    /**
     * Get the request URL
     */
    public function url(): string
    {
        return $this->scheme() . '://' . $this->host() . $this->path();
    }

    /**
     * Get the full URL with query string
     */
    public function fullUrl(): string
    {
        $url = $this->url();
        $query = $this->server['QUERY_STRING'] ?? '';
        
        return $query ? $url . '?' . $query : $url;
    }

    /**
     * Get the request path (without query string)
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        return $path === '' ? '/' : $path;
    }

    /**
     * Get the request scheme (http or https)
     */
    public function scheme(): string
    {
        $https = $this->server['HTTPS'] ?? '';
        return (!empty($https) && $https !== 'off') ? 'https' : 'http';
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function secure(): bool
    {
        return $this->scheme() === 'https';
    }

    /**
     * Get the host
     */
    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get the port
     */
    public function port(): int
    {
        return (int) ($this->server['SERVER_PORT'] ?? 80);
    }

    /**
     * Get the client IP address
     */
    public function ip(): string
    {
        // Check for forwarded IP (behind proxy/load balancer)
        $forwardedFor = $this->server['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get the user agent
     */
    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get the raw request body
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set a route parameter
     */
    public function setRouteParam(string $key, mixed $value): void
    {
        $this->routeParams[$key] = $value;
    }

    /**
     * Set multiple route parameters
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = array_merge($this->routeParams, $params);
    }

    /**
     * Get a route parameter
     */
    public function route(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParams;
        }
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get server parameter
     */
    public function server(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? $default;
    }

    /**
     * Parse headers from server array
     */
    protected function parseHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Normalize uploaded files array
     */
    protected function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $normalized[$key] = $file;
            } elseif (is_array($file)) {
                if (isset($file['tmp_name'])) {
                    // Single file
                    $normalized[$key] = new UploadedFile(
                        $file['tmp_name'],
                        $file['name'] ?? '',
                        $file['type'] ?? '',
                        $file['error'] ?? UPLOAD_ERR_NO_FILE,
                        $file['size'] ?? 0
                    );
                } else {
                    // Multiple files or nested
                    $normalized[$key] = $this->normalizeFiles($file);
                }
            }
        }

        return $normalized;
    }
}
