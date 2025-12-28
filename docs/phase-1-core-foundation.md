# Phase 1: Core Foundation

**Duration:** Weeks 1-2  
**Status:** ✅ Complete  
**Priority:** Critical - All other phases depend on this

---

## 🎯 Phase Objectives

Establish the fundamental building blocks that all other framework components will rely on.

---

## 📋 Components to Build

### 1. Autoloader (PSR-4 Compatible)

**File:** `/framework/Core/Autoloader.php`

#### Implementation Steps:
1. [x] Create `Autoloader` class with static `register()` method
2. [x] Implement namespace-to-directory mapping logic
3. [x] Add `spl_autoload_register()` integration
4. [x] Handle class file location resolution
5. [x] Add error handling for missing classes
6. [x] Support multiple namespace prefixes

#### Code Structure:
```php
<?php
namespace Framework\Core;

class Autoloader
{
    private static array $namespaces = [];
    
    public static function register(): void
    public static function addNamespace(string $prefix, string $baseDir): void
    private static function loadClass(string $class): bool
}
```

#### Usage Example:
```php
require_once 'framework/Core/Autoloader.php';
Autoloader::register();
Autoloader::addNamespace('App\\', __DIR__ . '/app/');
```

#### Testing Checklist:
- [x] Test namespace resolution
- [x] Test missing class handling
- [x] Test multiple namespace support

---

### 2. Configuration System

**Directory:** `/config/`  
**Files:** `app.php`, `database.php`, `auth.php`, `cache.php`

#### Implementation Steps:
1. [x] Create `/framework/Core/Config.php` class
2. [x] Implement array-based config loading
3. [x] Add dot notation accessor: `config('database.host')`
4. [x] Support environment-specific configs (dev/staging/prod)
5. [x] Create helper function in `/framework/Helpers/helpers.php`
6. [ ] Add config caching for production

#### Code Structure:
```php
<?php
namespace Framework\Core;

class Config
{
    private static array $items = [];
    
    public static function load(string $path): void
    public static function get(string $key, $default = null): mixed
    public static function set(string $key, $value): void
    public static function has(string $key): bool
    public static function all(): array
}
```

#### Config File Template (`config/app.php`):
```php
<?php
return [
    'name' => 'DIS Framework',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost',
    'timezone' => 'UTC',
];
```

#### Testing Checklist:
- [x] Test dot notation access
- [x] Test default values
- [x] Test environment overrides

---

### 3. Request Object

**File:** `/framework/Http/Request.php`

#### Implementation Steps:
1. [x] Create `Request` class with static factory method
2. [x] Implement GET/POST data access
3. [x] Add header reading methods
4. [x] Handle cookies and sessions
5. [x] Implement file upload access
6. [x] Add HTTP method detection (including spoofing)
7. [x] Parse JSON request bodies

#### Code Structure:
```php
<?php
namespace Framework\Http;

class Request
{
    public static function capture(): self
    public function input(string $key, $default = null): mixed
    public function query(string $key, $default = null): mixed
    public function post(string $key, $default = null): mixed
    public function header(string $key, $default = null): ?string
    public function cookie(string $key, $default = null): mixed
    public function file(string $key): ?UploadedFile
    public function method(): string
    public function isMethod(string $method): bool
    public function ajax(): bool
    public function url(): string
    public function path(): string
    public function all(): array
}
```

#### Testing Checklist:
- [x] Test input retrieval
- [x] Test header parsing
- [x] Test method spoofing
- [x] Test JSON body parsing

---

### 4. Response Object

**File:** `/framework/Http/Response.php`

#### Implementation Steps:
1. [x] Create `Response` class
2. [x] Implement status code management
3. [x] Add header management
4. [x] Handle content types
5. [x] Create redirect helper
6. [x] Implement JSON response formatting
7. [x] Add cookie setting

#### Code Structure:
```php
<?php
namespace Framework\Http;

class Response
{
    public function __construct($content = '', int $status = 200, array $headers = [])
    public function setContent($content): self
    public function setStatusCode(int $code): self
    public function header(string $key, string $value): self
    public function json($data, int $status = 200): self
    public function redirect(string $url, int $status = 302): self
    public function cookie(string $name, string $value, int $minutes = 0): self
    public function send(): void
}
```

#### Testing Checklist:
- [x] Test status codes
- [x] Test JSON responses
- [x] Test redirects
- [x] Test header setting

---

### 5. Basic Router

**File:** `/framework/Routing/Router.php`

#### Implementation Steps:
1. [x] Create `Router` class with static route registration
2. [x] Implement GET/POST/PUT/DELETE/PATCH methods
3. [x] Add route parameter parsing: `/user/{id}`
4. [x] Implement named routes: `route('user.show', ['id' => 1])`
5. [x] Add Controller@method syntax support
6. [x] Implement route dispatch logic
7. [x] Handle 404 not found cases

#### Code Structure:
```php
<?php
namespace Framework\Routing;

class Router
{
    private static array $routes = [];
    private static array $namedRoutes = [];
    
    public static function get(string $uri, $action): Route
    public static function post(string $uri, $action): Route
    public static function put(string $uri, $action): Route
    public static function delete(string $uri, $action): Route
    public static function match(array $methods, string $uri, $action): Route
    public static function dispatch(Request $request): Response
    public static function route(string $name, array $params = []): string
}
```

#### Usage Example:
```php
Router::get('/home', 'HomeController@index')->name('home');
Router::get('/user/{id}', 'UserController@show')->name('user.show');
Router::post('/user', 'UserController@store');
```

#### Testing Checklist:
- [x] Test route registration
- [x] Test parameter extraction
- [x] Test named route URL generation
- [x] Test controller dispatching

---

### 6. Database Connection Manager

**File:** `/framework/Database/Connection.php`

#### Implementation Steps:
1. [x] Create `Connection` class with PDO wrapper
2. [x] Implement connection configuration from `config/database.php`
3. [x] Add prepared statement support
4. [x] Support multiple database connections
5. [x] Implement connection pooling/reuse
6. [x] Add transaction support

#### Code Structure:
```php
<?php
namespace Framework\Database;

class Connection
{
    private static array $connections = [];
    
    public static function connection(string $name = 'default'): PDO
    public static function query(string $sql, array $bindings = []): PDOStatement
    public static function select(string $sql, array $bindings = []): array
    public static function insert(string $sql, array $bindings = []): int
    public static function update(string $sql, array $bindings = []): int
    public static function delete(string $sql, array $bindings = []): int
    public static function beginTransaction(): void
    public static function commit(): void
    public static function rollback(): void
}
```

#### Config Template (`config/database.php`):
```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'dis_framework',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

#### Testing Checklist:
- [x] Test connection establishment
- [x] Test prepared statements
- [x] Test transactions
- [x] Test multiple connections

---

### 7. Error & Exception Handling

**Files:** `/framework/Core/ErrorHandler.php`, `/framework/Core/ExceptionHandler.php`

#### Implementation Steps:
1. [x] Create `ErrorHandler` class for PHP errors
2. [x] Create `ExceptionHandler` class for exceptions
3. [x] Implement `set_error_handler()` and `set_exception_handler()`
4. [x] Add error logging to files
5. [x] Create development vs production modes
6. [x] Implement HTTP exception classes (404, 500)
7. [x] Add debug view for development

#### Code Structure:
```php
<?php
namespace Framework\Core;

class ErrorHandler
{
    public static function register(): void
    public static function handleError(int $level, string $message, string $file, int $line): bool
}

class ExceptionHandler
{
    public static function register(): void
    public static function handle(Throwable $e): void
    private static function renderForDevelopment(Throwable $e): void
    private static function renderForProduction(Throwable $e): void
    private static function log(Throwable $e): void
}
```

#### Testing Checklist:
- [x] Test error catching
- [x] Test exception handling
- [x] Test log file creation
- [x] Test dev vs prod modes

---

### 8. Front Controller

**File:** `/public/index.php`

#### Implementation Steps:
1. [x] Create front controller entry point
2. [x] Set up autoloader registration
3. [x] Load configuration
4. [x] Register error handlers
5. [x] Capture request
6. [x] Dispatch to router
7. [x] Send response

#### Code Structure:
```php
<?php
// public/index.php

define('BASE_PATH', dirname(__DIR__));

// Register autoloader
require_once BASE_PATH . '/framework/Core/Autoloader.php';
Framework\Core\Autoloader::register();

// Load helpers
require_once BASE_PATH . '/framework/Helpers/helpers.php';

// Load configuration
Framework\Core\Config::load(BASE_PATH . '/config');

// Register error handlers
Framework\Core\ErrorHandler::register();
Framework\Core\ExceptionHandler::register();

// Capture request and dispatch
$request = Framework\Http\Request::capture();
$response = Framework\Routing\Router::dispatch($request);
$response->send();
```

---

## 📅 Week-by-Week Schedule

### Week 1
| Day | Task |
|-----|------|
| Day 1-2 | Autoloader + Configuration System |
| Day 3 | Request Object |
| Day 4 | Response Object |
| Day 5 | Unit tests for above components |

### Week 2
| Day | Task |
|-----|------|
| Day 1-2 | Router implementation |
| Day 3 | Database Connection Manager |
| Day 4 | Error & Exception Handling |
| Day 5 | Front Controller + Integration testing |

---

## ✅ Phase Completion Criteria

- [ ] All 8 components implemented
- [ ] Unit tests passing for each component
- [ ] Front controller successfully boots
- [ ] Basic route to controller flow working
- [ ] Database connection established
- [ ] Errors/exceptions properly caught and logged
- [ ] Documentation updated
