# Error Handling

## Overview

DIS Framework provides comprehensive error and exception handling with different behaviors for development and production environments.

## Configuration

Error handling behavior is controlled by the `debug` setting in `config/app.php`:

```php
return [
    'debug' => true,  // true for development, false for production
];
```

## Error Handler

The `ErrorHandler` class converts PHP errors to exceptions:

```php
use Framework\Core\ErrorHandler;

// Registered automatically in public/index.php
ErrorHandler::register();

// Manual configuration
ErrorHandler::setDebug(true);
ErrorHandler::setLogPath('/path/to/logs/error.log');
```

### Error Logging

Errors are logged to `storage/logs/error-YYYY-MM-DD.log`:

```
[2024-12-28 22:00:00] ErrorException: Undefined variable $user in /app/Controllers/UserController.php:25
Stack trace:
#0 /framework/Core/ErrorHandler.php(45): handleError()
#1 /app/Controllers/UserController.php(25): ...
```

## Exception Handler

The `ExceptionHandler` renders exceptions appropriately:

```php
use Framework\Core\ExceptionHandler;

// Registered automatically in public/index.php
ExceptionHandler::register();

// Set debug mode
ExceptionHandler::setDebug(false);
```

### Development Mode

When `debug = true`, exceptions display:
- Exception class name
- Error message
- File and line number
- Source code context
- Full stack trace

### Production Mode

When `debug = false`, users see:
- Generic error page
- HTTP status code
- No sensitive information exposed

## HTTP Exceptions

Use HTTP exceptions for common error responses:

```php
use Framework\Core\HttpException;
use Framework\Core\NotFoundException;
use Framework\Core\UnauthorizedException;
use Framework\Core\ForbiddenException;

// Generic HTTP exception
throw new HttpException(503, 'Service temporarily unavailable');

// 404 Not Found
throw new NotFoundException('User not found');

// 401 Unauthorized
throw new UnauthorizedException('Please log in');

// 403 Forbidden
throw new ForbiddenException('Access denied');
```

### In Controllers

```php
class UserController
{
    public function show(Request $request, string $id)
    {
        $user = $this->findUser($id);
        
        if (!$user) {
            throw new NotFoundException("User {$id} not found");
        }
        
        return Response::json($user);
    }
}
```

### In Routes

```php
Router::get('/users/{id}', function (Request $request, string $id) {
    $user = findUser($id);
    
    if (!$user) {
        throw new NotFoundException();
    }
    
    return $user;
});
```

## Validation Exception

For form validation errors:

```php
use Framework\Core\ValidationException;

$errors = [
    'email' => ['The email field is required.'],
    'password' => ['The password must be at least 8 characters.'],
];

throw new ValidationException($errors, 'Validation failed');
```

The exception includes:
- HTTP 422 status code
- Array of validation errors
- Custom message

## Custom Exception Handlers

Register handlers for specific exception types:

```php
use Framework\Core\ExceptionHandler;

// Handle specific exception type
ExceptionHandler::registerHandler(
    \App\Exceptions\PaymentException::class,
    function ($exception) {
        // Log to payment error log
        // Notify admin
        // Render custom error page
    }
);
```

## JSON Error Responses

When the request expects JSON (via `Accept: application/json` header or AJAX), errors return JSON:

**Development:**
```json
{
    "error": true,
    "message": "User not found",
    "exception": "Framework\\Core\\NotFoundException",
    "file": "/app/Controllers/UserController.php",
    "line": 25,
    "trace": [...]
}
```

**Production:**
```json
{
    "error": true,
    "message": "Not Found"
}
```

## Manual Error Handling

### Try-Catch Blocks

```php
try {
    $result = riskyOperation();
} catch (\Exception $e) {
    // Log the error
    ErrorHandler::log($e);
    
    // Return error response
    return Response::json([
        'error' => 'Operation failed'
    ], 500);
}
```

### Logging Errors Manually

```php
use Framework\Core\ErrorHandler;

try {
    // ...
} catch (\Exception $e) {
    ErrorHandler::log($e);
}
```

## Best Practices

### 1. Use Specific Exceptions

```php
// Good
throw new NotFoundException('Product not found');
throw new UnauthorizedException('Invalid credentials');

// Avoid
throw new \Exception('Something went wrong');
```

### 2. Don't Expose Sensitive Data

```php
// Bad - exposes internal details
throw new \Exception("Database error: " . $pdo->errorInfo()[2]);

// Good - generic message
throw new HttpException(500, 'A database error occurred');
```

### 3. Always Disable Debug in Production

```php
// config/app.php
return [
    'debug' => env('APP_DEBUG', false),
];
```

### 4. Monitor Error Logs

Regularly check `storage/logs/` for errors in production.

### 5. Handle Expected Errors Gracefully

```php
Router::get('/users/{id}', function (Request $request, string $id) {
    $user = DB::table('users')->find($id);
    
    if (!$user) {
        // Expected case - handle gracefully
        if ($request->expectsJson()) {
            return Response::json(['error' => 'User not found'], 404);
        }
        throw new NotFoundException();
    }
    
    return $user;
});
```

## Error Pages

### Custom 404 Page

Create a custom 404 handler:

```php
// In routes/web.php
Router::get('/404', function () {
    return new Response(
        file_get_contents(base_path('resources/views/errors/404.html')),
        404
    );
})->name('errors.404');
```

### Custom 500 Page

For production, you can customize the error page by modifying the `renderProductionView` method or registering a custom handler.

## Summary

| Environment | Behavior |
|-------------|----------|
| Development (`debug: true`) | Detailed errors, stack traces, source code |
| Production (`debug: false`) | Generic error pages, errors logged to file |

Always ensure `debug` is `false` in production to prevent exposing sensitive information.
