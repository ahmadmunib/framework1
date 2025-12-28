# Phase 2: Developer Experience

**Duration:** Weeks 3-4  
**Status:** ⚪ Not Started  
**Dependencies:** Phase 1 Complete

---

## 🎯 Phase Objectives

Build the features that make daily development productive and enjoyable. Focus on authentication, views, forms, and testing.

---

## 📋 Components to Build

### 1. Template Engine

**Directory:** `/framework/View/`

#### Implementation Steps:
1. [ ] Create `View` class with render method
2. [ ] Implement Blade-like syntax parser
3. [ ] Add `@extends` and `@section` directives
4. [ ] Implement `@include` for partials
5. [ ] Add `@if`, `@else`, `@endif` conditionals
6. [ ] Implement `@foreach`, `@for`, `@while` loops
7. [ ] Add `{{ $var }}` echo with escaping
8. [ ] Add `{!! $var !!}` raw echo
9. [ ] Create view composers for shared data
10. [ ] Implement view caching

#### Supported Directives:
```
@extends('layout')
@section('content') ... @endsection
@yield('content')
@include('partials.header')
@if($condition) ... @elseif ... @else ... @endif
@foreach($items as $item) ... @endforeach
@for($i = 0; $i < 10; $i++) ... @endfor
@while($condition) ... @endwhile
@csrf
{{ $escaped }}
{!! $raw !!}
```

#### Code Structure:
```php
<?php
namespace Framework\View;

class View
{
    public static function make(string $view, array $data = []): self
    public function render(): string
    public function with(string $key, $value): self
}

class ViewCompiler
{
    public function compile(string $template): string
    private function compileExtends(string $content): string
    private function compileSections(string $content): string
    private function compileEchos(string $content): string
    private function compileStatements(string $content): string
}
```

#### Testing Checklist:
- [ ] Test variable echo
- [ ] Test conditionals
- [ ] Test loops
- [ ] Test layouts/sections
- [ ] Test includes
- [ ] Test escaping

---

### 2. Authentication System

**Directory:** `/framework/Auth/`

#### Implementation Steps:
1. [ ] Create `Auth` facade class
2. [ ] Implement user login with credentials
3. [ ] Add session-based authentication
4. [ ] Implement logout functionality
5. [ ] Add password hashing (bcrypt via `password_hash`)
6. [ ] Create "remember me" functionality
7. [ ] Implement authentication guards
8. [ ] Add `auth()` helper function

#### Code Structure:
```php
<?php
namespace Framework\Auth;

class Auth
{
    public static function attempt(array $credentials, bool $remember = false): bool
    public static function login(User $user, bool $remember = false): void
    public static function logout(): void
    public static function check(): bool
    public static function guest(): bool
    public static function user(): ?User
    public static function id(): ?int
}

class SessionGuard
{
    public function attempt(array $credentials): bool
    public function validate(array $credentials): bool
    public function login(Authenticatable $user): void
    public function logout(): void
}
```

#### Database Requirements:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Testing Checklist:
- [ ] Test successful login
- [ ] Test failed login
- [ ] Test logout
- [ ] Test remember me
- [ ] Test password verification
- [ ] Test session persistence

---

### 3. Middleware Pipeline

**Directory:** `/framework/Http/Middleware/`

#### Implementation Steps:
1. [ ] Create `Middleware` interface
2. [ ] Implement middleware pipeline
3. [ ] Add global middleware support
4. [ ] Implement route-specific middleware
5. [ ] Create built-in middleware:
   - [ ] `AuthMiddleware` - Check authenticated
   - [ ] `GuestMiddleware` - Check guest
   - [ ] `VerifyCsrfToken` - CSRF protection
   - [ ] `MaintenanceMode` - Maintenance check

#### Code Structure:
```php
<?php
namespace Framework\Http\Middleware;

interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}

class Pipeline
{
    private array $middleware = [];
    
    public function through(array $middleware): self
    public function send(Request $request): Response
}
```

#### Usage Example:
```php
Router::get('/dashboard', 'DashboardController@index')
    ->middleware(['auth', 'verified']);

Router::group(['middleware' => ['auth']], function() {
    Router::get('/profile', 'ProfileController@show');
});
```

#### Testing Checklist:
- [ ] Test middleware execution order
- [ ] Test request modification
- [ ] Test response modification
- [ ] Test middleware termination

---

### 4. CSRF Protection

**File:** `/framework/Http/Middleware/VerifyCsrfToken.php`

#### Implementation Steps:
1. [ ] Generate CSRF tokens per session
2. [ ] Create `@csrf` view directive
3. [ ] Implement token validation middleware
4. [ ] Add token to meta tag for AJAX
5. [ ] Support excluded URIs
6. [ ] Implement token rotation

#### Code Structure:
```php
<?php
namespace Framework\Http\Middleware;

class VerifyCsrfToken implements Middleware
{
    protected array $except = [];
    
    public function handle(Request $request, callable $next): Response
    protected function tokensMatch(Request $request): bool
    protected function addCookieToResponse(Response $response): Response
}
```

#### Usage:
```html
<form method="POST" action="/submit">
    @csrf
    <input type="text" name="name">
    <button type="submit">Submit</button>
</form>
```

---

### 5. Input Validation

**File:** `/framework/Validation/Validator.php`

#### Implementation Steps:
1. [ ] Create `Validator` class
2. [ ] Implement core validation rules:
   - [ ] `required` - Field must be present
   - [ ] `email` - Valid email format
   - [ ] `min:n` - Minimum length/value
   - [ ] `max:n` - Maximum length/value
   - [ ] `numeric` - Numeric value
   - [ ] `string` - String value
   - [ ] `confirmed` - Matches `{field}_confirmation`
   - [ ] `unique:table,column` - Unique in database
   - [ ] `exists:table,column` - Exists in database
   - [ ] `in:val1,val2` - In list of values
   - [ ] `regex:pattern` - Matches regex
3. [ ] Add custom rule support
4. [ ] Implement error message formatting
5. [ ] Create Form Request classes

#### Code Structure:
```php
<?php
namespace Framework\Validation;

class Validator
{
    public static function make(array $data, array $rules): self
    public function validate(): array
    public function fails(): bool
    public function passes(): bool
    public function errors(): MessageBag
    public function validated(): array
}

class MessageBag
{
    public function first(string $key): ?string
    public function get(string $key): array
    public function all(): array
    public function has(string $key): bool
}
```

#### Usage Example:
```php
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
]);

if ($validator->fails()) {
    return back()->withErrors($validator);
}
```

#### Testing Checklist:
- [ ] Test each validation rule
- [ ] Test custom rules
- [ ] Test error messages
- [ ] Test validated data extraction

---

### 6. Form Helpers

**File:** `/framework/Html/FormBuilder.php`

#### Implementation Steps:
1. [ ] Create `Form` facade class
2. [ ] Implement form open/close
3. [ ] Add input type generators
4. [ ] Implement model binding
5. [ ] Add error display helpers
6. [ ] Create select/checkbox/radio helpers

#### Code Structure:
```php
<?php
namespace Framework\Html;

class FormBuilder
{
    public static function open(array $options = []): string
    public static function close(): string
    public static function text(string $name, $value = null, array $attrs = []): string
    public static function email(string $name, $value = null, array $attrs = []): string
    public static function password(string $name, array $attrs = []): string
    public static function textarea(string $name, $value = null, array $attrs = []): string
    public static function select(string $name, array $options, $selected = null): string
    public static function checkbox(string $name, $value = 1, bool $checked = false): string
    public static function radio(string $name, $value, bool $checked = false): string
    public static function submit(string $value = 'Submit', array $attrs = []): string
    public static function model($model): void
}
```

#### Usage Example:
```php
{!! Form::open(['route' => 'user.update', 'method' => 'PUT']) !!}
    {!! Form::text('name', $user->name) !!}
    {!! Form::email('email', $user->email) !!}
    {!! Form::submit('Update') !!}
{!! Form::close() !!}
```

---

### 7. File Upload Handler

**File:** `/framework/Http/FileUpload.php`

#### Implementation Steps:
1. [ ] Create `UploadedFile` class
2. [ ] Implement file validation (type, size, extension)
3. [ ] Add secure file storage
4. [ ] Generate unique filenames
5. [ ] Support multiple file uploads
6. [ ] Implement storage drivers (local, S3 stub)

#### Code Structure:
```php
<?php
namespace Framework\Http;

class UploadedFile
{
    public function isValid(): bool
    public function getClientOriginalName(): string
    public function getClientOriginalExtension(): string
    public function getMimeType(): string
    public function getSize(): int
    public function store(string $path, string $disk = 'local'): string
    public function storeAs(string $path, string $name, string $disk = 'local'): string
}
```

#### Validation Rules:
```php
$validator = Validator::make($request->all(), [
    'avatar' => 'required|file|image|max:2048',
    'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
]);
```

---

### 8. Basic Testing Framework

**Directory:** `/framework/Testing/`

#### Implementation Steps:
1. [ ] Create `TestCase` base class
2. [ ] Implement assertion library
3. [ ] Build test runner with CLI
4. [ ] Add test discovery (scan `/tests/`)
5. [ ] Implement test reporting
6. [ ] Add setup/teardown methods

#### Core Assertions:
```php
<?php
namespace Framework\Testing;

class Assert
{
    public static function assertEquals($expected, $actual, string $message = ''): void
    public static function assertTrue($condition, string $message = ''): void
    public static function assertFalse($condition, string $message = ''): void
    public static function assertNull($value, string $message = ''): void
    public static function assertNotNull($value, string $message = ''): void
    public static function assertContains($needle, $haystack, string $message = ''): void
    public static function assertInstanceOf(string $class, $object, string $message = ''): void
    public static function assertCount(int $count, $array, string $message = ''): void
}
```

#### Test Runner CLI:
```bash
php framework/cli test
php framework/cli test --filter=UserTest
php framework/cli test tests/Unit/
```

---

### 9. Pagination System

**File:** `/framework/Database/Paginator.php`

#### Implementation Steps:
1. [ ] Create `Paginator` class
2. [ ] Calculate pagination metadata
3. [ ] Generate pagination links
4. [ ] Implement URL generation
5. [ ] Add view integration

#### Code Structure:
```php
<?php
namespace Framework\Database;

class Paginator
{
    public function __construct(array $items, int $total, int $perPage, int $currentPage)
    public function items(): array
    public function total(): int
    public function perPage(): int
    public function currentPage(): int
    public function lastPage(): int
    public function hasMorePages(): bool
    public function links(): string
    public function toArray(): array
}
```

#### Usage Example:
```php
$users = User::paginate(15);

// In view
@foreach($users as $user)
    {{ $user->name }}
@endforeach

{{ $users->links() }}
```

---

## 📅 Week-by-Week Schedule

### Week 3
| Day | Task |
|-----|------|
| Day 1-2 | Template Engine (compiler + directives) |
| Day 3 | Authentication System |
| Day 4 | Middleware Pipeline |
| Day 5 | CSRF Protection + Testing |

### Week 4
| Day | Task |
|-----|------|
| Day 1 | Input Validation |
| Day 2 | Form Helpers |
| Day 3 | File Upload Handler |
| Day 4 | Testing Framework |
| Day 5 | Pagination + Integration testing |

---

## ✅ Phase Completion Criteria

- [ ] Template engine renders views correctly
- [ ] Users can login/logout successfully
- [ ] Middleware intercepts requests properly
- [ ] CSRF protection blocks forged requests
- [ ] Validation catches invalid input
- [ ] Forms render with proper HTML
- [ ] File uploads work securely
- [ ] Tests can be run via CLI
- [ ] Pagination displays correctly
- [ ] All unit tests passing
