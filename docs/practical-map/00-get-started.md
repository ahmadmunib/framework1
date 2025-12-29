---
title: Get Started – Hello World
objective: Scaffold the project via Composer, boot it locally, and render a custom Hello World page.
prerequisites:
  - PHP 8.1+
  - Writable storage directories (logs/cache/uploads)
  - Web server (php -S or Apache/Nginx)
---

## 0. Scaffold via Composer

1. Run the installer (replace `my-app` with your folder name):
   ```bash
   composer create-project ahmadmunib/framework1 my-app
   ```
2. Enter the directory:
   ```bash
   cd my-app
   ```
3. (Optional) Commit the clean slate so you can track tutorial changes from a known state.

> ✅ **Checkpoint:** `composer.json` and the `framework/`, `app/`, `docs/` folders should now exist in your project directory.

## 1. Initialize the Workspace

1. Copy `.env.example` to `.env` (optional for now but recommended).
2. Ensure storage directories exist:
   ```bash
   mkdir -p storage/logs storage/cache storage/uploads
   ```
3. Start a local server from the project root:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Visit `http://localhost:8000` and verify the default DIS splash page loads.

> ✅ **Checkpoint:** If you see the gradient landing page defined in `routes/web.php`, your environment is healthy.

## 2. Create Your First Route

1. Open `routes/web.php`.
2. Add a new route under the existing home route:
   ```php
   use Framework\Http\Request;
   use Framework\Http\Response;
   use Framework\Routing\Router;

   Router::get('/hello', function (Request $request) {
       $name = $request->input('name', 'World');

       return new Response("<h1>Hello, {$name}!</h1>", 200, [
           'Content-Type' => 'text/html; charset=UTF-8',
       ]);
   })->name('hello.simple');
   ```
3. Refresh `http://localhost:8000/hello?name=Sara` and confirm the greeting updates dynamically.

> 💡 **Why HTML response?** This keeps things simple while showing how Request & Response objects interact with routing.

## 3. Extract a Controller (Optional Stretch)

1. Create `app/Http/Controllers/HelloController.php`:
   ```php
   <?php

   namespace App\Http\Controllers;

   use Framework\Http\Request;
   use Framework\Http\Response;

   class HelloController
   {
       public function __invoke(Request $request): Response
       {
           $name = $request->input('name', 'World');

           return new Response(view: "<h1>Hello, {$name}!</h1>");
       }
   }
   ```
2. Update the route to reference the controller:
   ```php
   Router::get('/hello/controller', \App\Http\Controllers\HelloController::class)
       ->name('hello.controller');
   ```
3. Reload `/hello/controller?name=Ada` to test.

## 4. Capture Learnings

- Note which files you touched (`routes/web.php`, `app/Http/Controllers/*`).
- Record any gotchas (e.g., missing namespace, typo in class name) at the bottom of this file for future runs.

---
**Next Module:** Move to `01-routing.md` to dive deeper into route parameters, grouping, and middleware.
