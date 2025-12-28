# Request & Response

## Request Object

The `Request` class provides access to all incoming HTTP request data.

### Accessing the Request

```php
use Framework\Http\Request;

// In a route closure
Router::get('/users', function (Request $request) {
    // $request is automatically injected
});

// In a controller
class UserController
{
    public function index(Request $request)
    {
        // Use $request
    }
}

// Create manually (for testing)
$request = Request::capture();  // From PHP globals
$request = Request::create('/users', 'GET', ['page' => 1]);  // Custom
```

### Input Data

```php
// Get input (checks POST, GET, JSON)
$name = $request->input('name');
$name = $request->input('name', 'default');

// Get all input
$all = $request->all();

// Get only specific keys
$credentials = $request->only(['email', 'password']);

// Get all except specific keys
$data = $request->except(['_token', 'password_confirmation']);

// Check if input exists
if ($request->has('email')) {
    // Key exists (may be empty)
}

if ($request->filled('email')) {
    // Key exists and is not empty
}
```

### Query Parameters (GET)

```php
// Get single parameter
$page = $request->query('page');
$page = $request->query('page', 1);

// Get all query parameters
$queryParams = $request->query();
```

### POST Data

```php
// Get single POST value
$name = $request->post('name');

// Get all POST data
$postData = $request->post();
```

### JSON Data

```php
// Check if request is JSON
if ($request->isJson()) {
    $data = $request->json();        // All JSON data
    $name = $request->json('name');  // Single value
}
```

### Request Information

```php
// HTTP method
$method = $request->method();      // GET, POST, PUT, etc.
$request->isMethod('POST');        // true/false
$request->isGet();                 // true/false
$request->isPost();                // true/false

// URL information
$url = $request->url();            // http://example.com/users
$fullUrl = $request->fullUrl();    // http://example.com/users?page=1
$path = $request->path();          // /users
$scheme = $request->scheme();      // http or https
$host = $request->host();          // example.com
$port = $request->port();          // 80

// Check HTTPS
if ($request->secure()) {
    // Request is over HTTPS
}
```

### Headers

```php
// Get a header
$contentType = $request->header('Content-Type');
$auth = $request->header('Authorization', 'default');

// Get all headers
$headers = $request->header();

// Check request type
if ($request->ajax()) {
    // XMLHttpRequest
}

if ($request->expectsJson()) {
    // Accepts: application/json
}
```

### Cookies

```php
$sessionId = $request->cookie('session_id');
$allCookies = $request->cookie();
```

### Client Information

```php
$ip = $request->ip();              // Client IP address
$userAgent = $request->userAgent(); // Browser user agent
```

### Route Parameters

```php
// Get route parameter
$id = $request->route('id');

// Get all route parameters
$params = $request->route();
```

### File Uploads

```php
// Check if file was uploaded
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
    
    // File information
    $name = $file->getClientOriginalName();  // photo.jpg
    $ext = $file->getClientOriginalExtension(); // jpg
    $mime = $file->getMimeType();             // image/jpeg
    $size = $file->getSize();                 // bytes
    
    // Validate
    if ($file->isValid()) {
        // Move to storage
        $path = $file->store('uploads/avatars');
        
        // Or with custom name
        $path = $file->storeAs('uploads/avatars', 'user-123.jpg');
    }
}

// Get all files
$files = $request->allFiles();
```

---

## Response Object

The `Response` class handles HTTP responses.

### Creating Responses

```php
use Framework\Http\Response;

// Basic response
$response = new Response('Hello World');
$response = new Response('Hello', 200);
$response = new Response('Hello', 200, ['X-Custom' => 'value']);

// Static factory
$response = Response::make('Hello', 200);
```

### Response Content

```php
// Set content
$response->setContent('New content');

// Get content
$content = $response->getContent();
```

### Status Codes

```php
// Set status
$response->setStatusCode(404);

// Get status
$code = $response->getStatusCode();

// Check status
$response->isOk();           // 200
$response->isSuccessful();   // 2xx
$response->isRedirect();     // 3xx
$response->isClientError();  // 4xx
$response->isServerError();  // 5xx
$response->isNotFound();     // 404
```

### Headers

```php
// Set header
$response->header('Content-Type', 'application/json');
$response->header('X-Custom', 'value');

// Set multiple headers
$response->withHeaders([
    'X-Header-One' => 'value1',
    'X-Header-Two' => 'value2',
]);

// Get header
$value = $response->getHeader('Content-Type');
$all = $response->getHeaders();
```

### JSON Responses

```php
// Create JSON response
$response = Response::json([
    'status' => 'success',
    'data' => ['id' => 1, 'name' => 'John']
]);

// With status code
$response = Response::json(['error' => 'Not found'], 404);

// In routes (arrays auto-convert)
Router::get('/api/user', function () {
    return ['id' => 1, 'name' => 'John'];
});
```

### Redirects

```php
// Simple redirect
$response = Response::redirect('/dashboard');

// With status code
$response = Response::redirect('/login', 301);  // Permanent
$response = Response::redirect('/dashboard', 302);  // Temporary
```

### Cookies

```php
// Set cookie
$response->cookie('name', 'value', 60);  // 60 minutes

// Full options
$response->cookie(
    name: 'session',
    value: 'abc123',
    minutes: 120,
    path: '/',
    domain: null,
    secure: true,
    httpOnly: true,
    sameSite: 'Lax'
);

// Remove cookie
$response->withoutCookie('name');
```

### File Downloads

```php
// Force download
$response = Response::download('/path/to/file.pdf');
$response = Response::download('/path/to/file.pdf', 'custom-name.pdf');

// Display inline (e.g., PDF in browser)
$response = Response::file('/path/to/document.pdf');
```

### No Content Response

```php
$response = Response::noContent();  // 204 No Content
```

### Sending Response

```php
// Send to browser
$response->send();

// Method chaining
Response::json(['status' => 'ok'])
    ->header('X-Custom', 'value')
    ->cookie('visited', 'true', 60)
    ->send();
```

## Common Patterns

### API Response Helper

```php
// In a controller
class ApiController
{
    protected function success($data, int $code = 200): Response
    {
        return Response::json([
            'success' => true,
            'data' => $data,
        ], $code);
    }
    
    protected function error(string $message, int $code = 400): Response
    {
        return Response::json([
            'success' => false,
            'error' => $message,
        ], $code);
    }
}
```

### Conditional Response

```php
Router::get('/users/{id}', function (Request $request, string $id) {
    $user = findUser($id);
    
    if (!$user) {
        if ($request->expectsJson()) {
            return Response::json(['error' => 'Not found'], 404);
        }
        return new Response('User not found', 404);
    }
    
    if ($request->expectsJson()) {
        return Response::json($user);
    }
    
    return "User: {$user->name}";
});
```

## Next Steps

- [Database Queries](./06-database.md)
- [Error Handling](./07-error-handling.md)
