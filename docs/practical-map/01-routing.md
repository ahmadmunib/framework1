---
title: Routing Mastery
objective: Understand every flavor of DIS Framework routing and implement hands-on exercises that mirror the official docs.
recommended-reading:
  - docs/official-documentation/04-routing.md
  - docs/official-documentation/05-request-response.md (for response helpers)
prerequisites:
  - Completed `00-get-started.md`
  - Local server running (`php -S localhost:8000 -t public` or web server)
---

## 1. Warm-Up Checklist

1. Open `routes/web.php` in your editor.
2. Keep `public/index.php` + `framework/Routing` handy for reference.
3. Clear browser cache or use a new tab to avoid cached responses.
4. Optional: add a `/debug/routes` endpoint (see Section 6) to inspect your work.

> ✅ **Checkpoint:** Run `php -l routes/web.php` to ensure syntax is valid before and after each block of edits.

## 2. Basic & Closure Routes

1. Append a simple GET route:
   ```php
   Router::get('/ping', fn () => 'pong');
   ```
2. Showcase dynamic string responses:
   ```php
   Router::get('/hello/{name}', function (Request $request, string $name) {
       return "Hello, {$name}!";
   });
   ```
3. Reload `/ping` and `/hello/Sara` in the browser.

> 📓 **Reflection:** Note how route parameters are injected into the closure signature.

## 3. Controller Actions

1. Create `app/Http/Controllers/UserController.php` with `index`, `show`, and `store` methods.
2. Register routes:
   ```php
   Router::get('/users', 'UserController@index')->name('users.index');
   Router::get('/users/{id}', 'UserController@show')->name('users.show');
   Router::post('/users', 'UserController@store')->name('users.store');
   ```
3. Use Postman or curl to hit the POST endpoint and ensure the controller receives Request data.

> 🔁 **Stretch:** Convert one route to use a fully-qualified controller namespace (e.g., `App\Http\Controllers\Admin\UserController`).

## 4. Parameters & Constraints

1. Add optional parameter route:
   ```php
   Router::get('/reports/{year?}', function (?string $year = null) {
       return $year ? "Reports for {$year}" : 'All Reports';
   });
   ```
2. Enforce constraints:
   ```php
   Router::get('/orders/{uuid}', 'OrderController@show')
       ->whereUuid('uuid');

   Router::get('/products/{slug}', 'ProductController@show')
       ->where('slug', '[a-z0-9-]+');
   ```
3. Hit an invalid URL (e.g., `/orders/not-a-uuid`) and confirm you receive a 404 handled by the router.

> 🧠 **Memory Hook:** Constraints live on the route definition chain—perfect for quick validation before controllers run.

## 5. Route Groups & Middleware

1. Define an admin prefix group:
   ```php
   Router::prefix('admin')->group(function () {
       Router::get('/dashboard', 'Admin\\DashboardController@index');
       Router::get('/users', 'Admin\\UserController@index');
   });
   ```
2. Chain middleware (create a stub middleware if needed):
   ```php
   Router::middleware(['auth'])->group(function () {
       Router::get('/account', 'AccountController@show');
   });
   ```
3. Nest groups to mirror `/api/v1` structure.

> 🔐 **Optional:** Implement a simple middleware that rejects unauthenticated requests to feel the full pipeline.

## 6. Route Debugging Utility

Add a debugging route per docs:
```php
Router::get('/debug/routes', function () {
    return Response::json(Router::getRoutes());
});
```
Visit `/debug/routes` and verify every route you added appears with method, URI, name, and action.

## 7. API Routes

1. Open `routes/api.php` and register:
   ```php
   Router::get('/status', fn () => ['status' => 'ok']);
   Router::get('/users/{id}', 'Api\\UserController@show');
   ```
2. Confirm URLs are automatically prefixed with `/api` (e.g., `/api/status`).
3. Use curl: `curl http://localhost:8000/api/status`.

> ✨ **Stretch:** Share logic between web + API controllers while returning different response formats.

## 8. Practice Log

- Record errors encountered (e.g., missing controller namespace, typo in constraint).
- Note useful tips (e.g., use `Router::route('name')` to generate URLs).
- Brainstorm how you could add rate limiting or versioned APIs next.

---
**Next Module:** Continue with `02-configuration.md` to master environment settings and config helpers.
