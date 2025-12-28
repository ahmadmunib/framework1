<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;

/**
 * HTTP Router
 * 
 * Handles route registration and dispatching.
 * 
 * @example
 * Router::get('/users', 'UserController@index');
 * Router::post('/users', 'UserController@store');
 * Router::get('/users/{id}', 'UserController@show');
 */
class Router
{
    /**
     * Registered routes
     */
    protected static array $routes = [];

    /**
     * Named routes
     */
    protected static array $namedRoutes = [];

    /**
     * Route group stack
     */
    protected static array $groupStack = [];

    /**
     * Current route being matched
     */
    protected static ?Route $currentRoute = null;

    /**
     * Register a GET route
     */
    public static function get(string $uri, mixed $action): Route
    {
        return self::addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a POST route
     */
    public static function post(string $uri, mixed $action): Route
    {
        return self::addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a PUT route
     */
    public static function put(string $uri, mixed $action): Route
    {
        return self::addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a PATCH route
     */
    public static function patch(string $uri, mixed $action): Route
    {
        return self::addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a DELETE route
     */
    public static function delete(string $uri, mixed $action): Route
    {
        return self::addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register a route for any HTTP method
     */
    public static function any(string $uri, mixed $action): Route
    {
        return self::addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }

    /**
     * Register a route for specific methods
     */
    public static function match(array $methods, string $uri, mixed $action): Route
    {
        $methods = array_map('strtoupper', $methods);
        return self::addRoute($methods, $uri, $action);
    }

    /**
     * Create a route group
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        
        $callback();
        
        array_pop(self::$groupStack);
    }

    /**
     * Add a route with prefix
     */
    public static function prefix(string $prefix): GroupRegistrar
    {
        return new GroupRegistrar(['prefix' => $prefix]);
    }

    /**
     * Add a route with middleware
     */
    public static function middleware(string|array $middleware): GroupRegistrar
    {
        return new GroupRegistrar(['middleware' => (array) $middleware]);
    }

    /**
     * Add a route to the collection
     */
    protected static function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $uri = self::applyGroupPrefix($uri);
        $middleware = self::getGroupMiddleware();
        
        $route = new Route($methods, $uri, $action);
        
        if (!empty($middleware)) {
            $route->middleware($middleware);
        }
        
        foreach ($methods as $method) {
            self::$routes[$method][] = $route;
        }
        
        return $route;
    }

    /**
     * Apply group prefix to URI
     */
    protected static function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        
        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        
        $uri = '/' . trim($uri, '/');
        
        return $prefix ? rtrim($prefix, '/') . $uri : $uri;
    }

    /**
     * Get merged group middleware
     */
    protected static function getGroupMiddleware(): array
    {
        $middleware = [];
        
        foreach (self::$groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        
        return $middleware;
    }

    /**
     * Register a named route
     */
    public static function registerNamedRoute(string $name, Route $route): void
    {
        self::$namedRoutes[$name] = $route;
    }

    /**
     * Generate URL for a named route
     */
    public static function route(string $name, array $parameters = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }
        
        $route = self::$namedRoutes[$name];
        $uri = $route->getUri();
        
        // Replace route parameters
        foreach ($parameters as $key => $value) {
            $uri = preg_replace('/\{' . $key . '\??\}/', (string) $value, $uri);
        }
        
        // Remove any remaining optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        
        // Check for missing required parameters
        if (preg_match('/\{[^}]+\}/', $uri)) {
            throw new \InvalidArgumentException("Missing required parameters for route [{$name}].");
        }
        
        return $uri;
    }

    /**
     * Dispatch the request to the router
     */
    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();
        
        // Find matching route
        $route = self::findRoute($method, $path);
        
        if ($route === null) {
            return self::handleNotFound($request);
        }
        
        self::$currentRoute = $route;
        
        // Extract route parameters
        $parameters = $route->extractParameters($path);
        $request->setRouteParams($parameters);
        
        // Execute middleware and route action
        return self::runRoute($route, $request);
    }

    /**
     * Find a matching route
     */
    protected static function findRoute(string $method, string $path): ?Route
    {
        $routes = self::$routes[$method] ?? [];
        
        foreach ($routes as $route) {
            if ($route->matches($path)) {
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Run the route action
     */
    protected static function runRoute(Route $route, Request $request): Response
    {
        $action = $route->getAction();
        
        // Handle closure
        if ($action instanceof \Closure) {
            $response = self::callAction($action, $request);
            return self::prepareResponse($response);
        }
        
        // Handle Controller@method string
        if (is_string($action) && str_contains($action, '@')) {
            return self::runControllerAction($action, $request);
        }
        
        // Handle array [Controller::class, 'method']
        if (is_array($action) && count($action) === 2) {
            return self::runControllerAction($action[0] . '@' . $action[1], $request);
        }
        
        throw new \RuntimeException('Invalid route action');
    }

    /**
     * Run a controller action
     */
    protected static function runControllerAction(string $action, Request $request): Response
    {
        [$controller, $method] = explode('@', $action);
        
        // Add default namespace if not fully qualified
        if (!str_contains($controller, '\\')) {
            $controller = 'App\\Http\\Controllers\\' . $controller;
        }
        
        if (!class_exists($controller)) {
            throw new \RuntimeException("Controller [{$controller}] not found.");
        }
        
        $instance = new $controller();
        
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method [{$method}] not found on controller [{$controller}].");
        }
        
        $response = self::callAction([$instance, $method], $request);
        
        return self::prepareResponse($response);
    }

    /**
     * Call an action with request
     */
    protected static function callAction(callable $action, Request $request): mixed
    {
        $params = $request->route();
        
        // Simple injection - pass request and route params
        return $action($request, ...array_values($params));
    }

    /**
     * Prepare the response
     */
    protected static function prepareResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }
        
        if (is_array($response) || is_object($response)) {
            return Response::json($response);
        }
        
        return new Response((string) $response);
    }

    /**
     * Handle 404 not found
     */
    protected static function handleNotFound(Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json(['error' => 'Not Found'], 404);
        }
        
        return new Response('404 Not Found', 404);
    }

    /**
     * Get current route
     */
    public static function current(): ?Route
    {
        return self::$currentRoute;
    }

    /**
     * Get all registered routes
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get all named routes
     */
    public static function getNamedRoutes(): array
    {
        return self::$namedRoutes;
    }

    /**
     * Clear all routes (useful for testing)
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$groupStack = [];
        self::$currentRoute = null;
    }
}

/**
 * Group Registrar for fluent route groups
 */
class GroupRegistrar
{
    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function prefix(string $prefix): self
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->attributes['middleware'] = (array) $middleware;
        return $this;
    }

    public function group(callable $callback): void
    {
        Router::group($this->attributes, $callback);
    }
}
