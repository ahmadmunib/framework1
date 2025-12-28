# Routing

## Overview

Routes define how your application responds to HTTP requests. All routes are defined in the `routes/` directory.

- `routes/web.php` - Web routes (HTML responses)
- `routes/api.php` - API routes (automatically prefixed with `/api`)

## Basic Routing

### Available Methods

```php
use Framework\Routing\Router;

Router::get('/users', $callback);      // GET request
Router::post('/users', $callback);     // POST request
Router::put('/users/{id}', $callback); // PUT request
Router::patch('/users/{id}', $callback); // PATCH request
Router::delete('/users/{id}', $callback); // DELETE request
Router::any('/users', $callback);      // Any HTTP method
```

### Closure Routes

```php
Router::get('/hello', function () {
    return 'Hello, World!';
});

Router::get('/user/{id}', function (Request $request, string $id) {
    return "User ID: {$id}";
});
```

### Controller Routes

```php
// Single action
Router::get('/users', 'UserController@index');
Router::get('/users/{id}', 'UserController@show');
Router::post('/users', 'UserController@store');

// With full namespace
Router::get('/admin/users', 'App\Http\Controllers\Admin\UserController@index');
```

## Route Parameters

### Required Parameters

```php
Router::get('/users/{id}', function (Request $request, string $id) {
    return "User: {$id}";
});

Router::get('/posts/{post}/comments/{comment}', function (Request $request, string $post, string $comment) {
    return "Post {$post}, Comment {$comment}";
});
```

### Optional Parameters

```php
Router::get('/users/{id?}', function (Request $request, ?string $id = null) {
    if ($id) {
        return "User: {$id}";
    }
    return "All users";
});
```

### Parameter Constraints

```php
// Numeric only
Router::get('/users/{id}', 'UserController@show')
    ->whereNumber('id');

// Alphabetic only
Router::get('/categories/{slug}', 'CategoryController@show')
    ->whereAlpha('slug');

// Alphanumeric
Router::get('/posts/{slug}', 'PostController@show')
    ->whereAlphaNumeric('slug');

// UUID format
Router::get('/orders/{uuid}', 'OrderController@show')
    ->whereUuid('uuid');

// Custom regex
Router::get('/users/{id}', 'UserController@show')
    ->where('id', '[0-9]+');

// Multiple constraints
Router::get('/users/{id}/posts/{slug}', 'PostController@show')
    ->where([
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
    ]);
```

## Named Routes

Name your routes for easy URL generation:

```php
Router::get('/users', 'UserController@index')->name('users.index');
Router::get('/users/{id}', 'UserController@show')->name('users.show');
Router::post('/users', 'UserController@store')->name('users.store');
```

### Generating URLs

```php
// Simple route
$url = Router::route('users.index');
// Result: /users

// With parameters
$url = Router::route('users.show', ['id' => 123]);
// Result: /users/123
```

## Route Groups

### Prefix Groups

```php
Router::group(['prefix' => 'admin'], function () {
    Router::get('/dashboard', 'Admin\DashboardController@index');
    // URL: /admin/dashboard
    
    Router::get('/users', 'Admin\UserController@index');
    // URL: /admin/users
});
```

### Nested Groups

```php
Router::group(['prefix' => 'api'], function () {
    Router::group(['prefix' => 'v1'], function () {
        Router::get('/users', 'Api\V1\UserController@index');
        // URL: /api/v1/users
    });
});
```

### Fluent Group Syntax

```php
Router::prefix('admin')->group(function () {
    Router::get('/dashboard', 'DashboardController@index');
});

Router::middleware(['auth'])->group(function () {
    Router::get('/profile', 'ProfileController@show');
});

Router::prefix('api')->middleware(['api'])->group(function () {
    Router::get('/users', 'ApiController@users');
});
```

## Response Types

### String Response

```php
Router::get('/hello', function () {
    return 'Hello, World!';
});
```

### Array/JSON Response

Arrays are automatically converted to JSON:

```php
Router::get('/api/users', function () {
    return [
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ];
});
```

### Response Object

```php
use Framework\Http\Response;

Router::get('/custom', function () {
    return new Response('Custom content', 201, [
        'X-Custom-Header' => 'value'
    ]);
});

Router::get('/json', function () {
    return Response::json(['status' => 'ok'], 200);
});

Router::get('/redirect', function () {
    return Response::redirect('/dashboard');
});
```

## Accessing Route Parameters in Controllers

```php
// In routes/web.php
Router::get('/users/{id}', 'UserController@show');

// In app/Http/Controllers/UserController.php
class UserController
{
    public function show(Request $request, string $id)
    {
        // $id contains the route parameter
        
        // Or access via request
        $id = $request->route('id');
        
        return "User ID: {$id}";
    }
}
```

## API Routes

Routes in `routes/api.php` are automatically prefixed with `/api`:

```php
// routes/api.php
Router::get('/users', 'Api\UserController@index');
// Accessible at: /api/users

Router::get('/users/{id}', 'Api\UserController@show');
// Accessible at: /api/users/123
```

## Route List

To see all registered routes, you can add a debug route:

```php
Router::get('/debug/routes', function () {
    $routes = Router::getRoutes();
    return Response::json($routes);
});
```

## Next Steps

- [Request & Response](./05-request-response.md)
- [Database Queries](./06-database.md)
