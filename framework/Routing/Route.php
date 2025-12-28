<?php

declare(strict_types=1);

namespace Framework\Routing;

/**
 * Route Definition
 * 
 * Represents a single route with its URI pattern, action, and attributes.
 */
class Route
{
    /**
     * HTTP methods this route responds to
     */
    protected array $methods;

    /**
     * The URI pattern
     */
    protected string $uri;

    /**
     * The route action
     */
    protected mixed $action;

    /**
     * Route name
     */
    protected ?string $name = null;

    /**
     * Route middleware
     */
    protected array $middleware = [];

    /**
     * Compiled regex pattern
     */
    protected ?string $pattern = null;

    /**
     * Parameter names from URI
     */
    protected array $parameterNames = [];

    /**
     * Where constraints
     */
    protected array $wheres = [];

    public function __construct(array $methods, string $uri, mixed $action)
    {
        $this->methods = $methods;
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
        
        $this->compilePattern();
    }

    /**
     * Set the route name
     */
    public function name(string $name): self
    {
        $this->name = $name;
        Router::registerNamedRoute($name, $this);
        return $this;
    }

    /**
     * Add middleware to the route
     */
    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    /**
     * Add a where constraint
     */
    public function where(string|array $name, ?string $expression = null): self
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->wheres[$key] = $value;
            }
        } else {
            $this->wheres[$name] = $expression;
        }
        
        $this->compilePattern();
        
        return $this;
    }

    /**
     * Constrain parameter to numeric values
     */
    public function whereNumber(string|array $parameters): self
    {
        foreach ((array) $parameters as $param) {
            $this->where($param, '[0-9]+');
        }
        return $this;
    }

    /**
     * Constrain parameter to alphabetic values
     */
    public function whereAlpha(string|array $parameters): self
    {
        foreach ((array) $parameters as $param) {
            $this->where($param, '[a-zA-Z]+');
        }
        return $this;
    }

    /**
     * Constrain parameter to alphanumeric values
     */
    public function whereAlphaNumeric(string|array $parameters): self
    {
        foreach ((array) $parameters as $param) {
            $this->where($param, '[a-zA-Z0-9]+');
        }
        return $this;
    }

    /**
     * Constrain parameter to UUID format
     */
    public function whereUuid(string|array $parameters): self
    {
        foreach ((array) $parameters as $param) {
            $this->where($param, '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        }
        return $this;
    }

    /**
     * Check if the route matches a path
     */
    public function matches(string $path): bool
    {
        $path = '/' . trim($path, '/');
        return (bool) preg_match($this->pattern, $path);
    }

    /**
     * Extract parameters from a path
     */
    public function extractParameters(string $path): array
    {
        $path = '/' . trim($path, '/');
        $parameters = [];
        
        if (preg_match($this->pattern, $path, $matches)) {
            foreach ($this->parameterNames as $name) {
                if (isset($matches[$name])) {
                    $parameters[$name] = $matches[$name];
                }
            }
        }
        
        return $parameters;
    }

    /**
     * Compile the URI pattern to regex
     */
    protected function compilePattern(): void
    {
        $uri = $this->uri;
        $this->parameterNames = [];
        
        // Match {param} or {param?} patterns (including preceding slash for optional)
        $pattern = preg_replace_callback(
            '/\/?(\{([a-zA-Z_][a-zA-Z0-9_]*)(\?)?\})/',
            function ($matches) {
                $hasSlash = str_starts_with($matches[0], '/');
                $name = $matches[2];
                $optional = isset($matches[3]);
                
                $this->parameterNames[] = $name;
                
                // Get constraint or default
                $constraint = $this->wheres[$name] ?? '[^/]+';
                
                if ($optional) {
                    // Make slash and parameter both optional
                    return '(?:/(?P<' . $name . '>' . $constraint . '))?';
                }
                
                return ($hasSlash ? '/' : '') . '(?P<' . $name . '>' . $constraint . ')';
            },
            $uri
        );
        
        // Escape forward slashes and anchors
        $pattern = '#^' . $pattern . '$#';
        
        $this->pattern = $pattern;
    }

    /**
     * Get the URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the action
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Get the methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get parameter names
     */
    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }
}
