# Architecture Suggestions & Best Practices

This document provides design recommendations for building the DIS Framework.

---

## 🏗️ Core Architecture Patterns

### 1. Service Container (Dependency Injection)

**Why:** Enables loose coupling, testability, and flexibility.

**Recommendation:** Implement a simple IoC container early in Phase 1.

```php
<?php
namespace Framework\Core;

class Container
{
    private static array $bindings = [];
    private static array $instances = [];
    
    public static function bind(string $abstract, $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }
    
    public static function singleton(string $abstract, $concrete): void
    {
        self::$bindings[$abstract] = ['concrete' => $concrete, 'singleton' => true];
    }
    
    public static function make(string $abstract): mixed
    {
        // Check singleton instances first
        if (isset(self::$instances[$abstract])) {
            return self::$instances[$abstract];
        }
        
        // Resolve from bindings or auto-wire
        $concrete = self::$bindings[$abstract] ?? $abstract;
        
        // Handle singleton pattern
        if (is_array($concrete) && ($concrete['singleton'] ?? false)) {
            $instance = self::build($concrete['concrete']);
            self::$instances[$abstract] = $instance;
            return $instance;
        }
        
        return self::build($concrete);
    }
    
    private static function build($concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete(self::class);
        }
        
        // Auto-wire using reflection
        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();
        
        if (!$constructor) {
            return new $concrete;
        }
        
        $dependencies = array_map(function ($param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                return self::make($type->getName());
            }
            return $param->getDefaultValue();
        }, $constructor->getParameters());
        
        return $reflector->newInstanceArgs($dependencies);
    }
}
```

---

### 2. Facade Pattern

**Why:** Provides clean, static-like API while maintaining testability.

```php
<?php
namespace Framework\Support;

abstract class Facade
{
    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }
    
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = Container::make(static::getFacadeAccessor());
        return $instance->$method(...$args);
    }
}

// Example: DB Facade
class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}

// Usage: DB::table('users')->get();
```

---

### 3. Repository Pattern (Optional)

**Why:** Separates data access logic from business logic.

```php
<?php
namespace App\Repositories;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): bool;
}

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    // ...
}
```

---

## 📁 Directory Structure Recommendations

### Suggested Structure:
```
project-root/
├── framework/                  # Framework core (don't modify in apps)
│   ├── Core/
│   │   ├── Autoloader.php
│   │   ├── Container.php
│   │   ├── Config.php
│   │   └── Application.php    # Bootstrap class
│   ├── Http/
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Kernel.php         # HTTP kernel
│   │   └── Middleware/
│   ├── Routing/
│   │   ├── Router.php
│   │   └── Route.php
│   ├── Database/
│   │   ├── Connection.php
│   │   ├── QueryBuilder.php
│   │   └── Model.php
│   └── Support/
│       ├── Facade.php
│       ├── Collection.php     # Array wrapper
│       └── Str.php            # String helpers
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/          # Form request validation
│   ├── Models/
│   ├── Services/              # Business logic
│   └── Repositories/          # Data access (optional)
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
│   ├── index.php
│   └── assets/
├── resources/
│   ├── views/
│   └── lang/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
└── tests/
```

---

## 🔧 Implementation Suggestions

### 1. Bootstrap Process

Create an `Application` class to centralize bootstrapping:

```php
<?php
// framework/Core/Application.php

namespace Framework\Core;

class Application
{
    private static ?self $instance = null;
    private string $basePath;
    
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }
    
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
        self::$instance = $this;
        
        $this->registerBaseBindings();
        $this->registerCoreProviders();
    }
    
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    public function run(): void
    {
        $request = Request::capture();
        $response = $this->handleRequest($request);
        $response->send();
    }
}
```

---

### 2. Collection Class

Wrap arrays with useful methods:

```php
<?php
namespace Framework\Support;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $items = [];
    
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }
    
    public function filter(callable $callback = null): self
    {
        return new static(array_filter($this->items, $callback));
    }
    
    public function first(callable $callback = null, $default = null): mixed
    {
        if (is_null($callback)) {
            return empty($this->items) ? $default : reset($this->items);
        }
        
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return $default;
    }
    
    public function pluck(string $key): self
    {
        return $this->map(fn($item) => data_get($item, $key));
    }
    
    public function toArray(): array
    {
        return $this->items;
    }
    
    // ... more methods: reduce, each, contains, unique, sort, etc.
}
```

---

### 3. String Helper Class

```php
<?php
namespace Framework\Support;

class Str
{
    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', strtolower($title));
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
        return trim($title, $separator);
    }
    
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit) . $end;
    }
    
    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
    
    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }
    
    public static function snake(string $value, string $delimiter = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . $delimiter . '$2', $value));
    }
    
    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
```

---

## 🔒 Security Recommendations

### 1. Password Handling
```php
// Always use password_hash with PASSWORD_BCRYPT or PASSWORD_ARGON2ID
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verification
if (password_verify($input, $hash)) {
    // Valid password
}
```

### 2. SQL Injection Prevention
```php
// ALWAYS use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// NEVER do this:
// $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### 3. XSS Prevention
```php
// In template engine, escape by default
// {{ $variable }} should output: htmlspecialchars($variable, ENT_QUOTES, 'UTF-8')

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
}
```

### 4. CSRF Token Generation
```php
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}
```

---

## ⚡ Performance Tips

### 1. Lazy Loading
```php
// Don't load everything at boot
// Use lazy loading for expensive operations
class Config
{
    private static array $loaded = [];
    
    public static function get(string $key, $default = null)
    {
        $file = explode('.', $key)[0];
        
        if (!isset(self::$loaded[$file])) {
            self::$loaded[$file] = require config_path("{$file}.php");
        }
        
        return data_get(self::$loaded[$file], substr($key, strlen($file) + 1), $default);
    }
}
```

### 2. Singleton Services
```php
// Use singletons for expensive-to-create services
Container::singleton('db', function () {
    return new Connection(config('database'));
});
```

### 3. Query Optimization
```php
// Use eager loading to prevent N+1 queries
$users = User::with('posts')->get(); // 2 queries

// Instead of:
$users = User::all(); // 1 query
foreach ($users as $user) {
    $user->posts; // N queries
}
```

---

## 🧪 Testing Strategy

### 1. Unit Tests
- Test individual classes in isolation
- Mock dependencies
- Fast execution

### 2. Integration Tests
- Test component interactions
- Use test database
- May be slower

### 3. Feature Tests
- Test complete user flows
- HTTP request simulation
- End-to-end verification

### Test Organization:
```
tests/
├── Unit/
│   ├── ConfigTest.php
│   ├── RouterTest.php
│   └── ValidatorTest.php
├── Feature/
│   ├── AuthenticationTest.php
│   ├── UserControllerTest.php
│   └── ApiTest.php
├── TestCase.php
└── CreatesApplication.php
```

---

## 📝 Coding Standards

### Follow PSR-12
- Use 4 spaces for indentation
- Opening braces on same line for control structures
- Opening braces on new line for classes/methods
- One class per file
- Declare strict types when possible

```php
<?php

declare(strict_types=1);

namespace App\Services;

class UserService
{
    public function __construct(
        private UserRepository $users,
        private Mailer $mailer
    ) {}
    
    public function register(array $data): User
    {
        $user = $this->users->create($data);
        
        if ($user) {
            $this->mailer->sendWelcome($user);
        }
        
        return $user;
    }
}
```

---

## 🚀 Deployment Checklist

1. **Environment Configuration**
   - [ ] Set `APP_ENV=production`
   - [ ] Set `APP_DEBUG=false`
   - [ ] Configure production database
   - [ ] Set proper file permissions

2. **Caching**
   - [ ] Run `php framework/cli config:cache`
   - [ ] Run `php framework/cli route:cache`
   - [ ] Run `php framework/cli view:cache`

3. **Security**
   - [ ] Verify `.env` is not web-accessible
   - [ ] Check all sensitive files are protected
   - [ ] Enable HTTPS
   - [ ] Run `php framework/cli security:audit`

4. **Performance**
   - [ ] Enable OPcache
   - [ ] Configure proper database indexes
   - [ ] Set up CDN for static assets
