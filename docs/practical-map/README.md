# Practical Map Roadmap

This folder is your hands-on companion to the official documentation. Each module contains concise goals, required reading, and practice drills so you can build intuition for every framework subsystem.

## How to Use This Map

1. **Start with `00-get-started.md`.** You will boot the framework and ship a "Hello World" page to confirm your environment is healthy.
2. **Pick the next module based on your learning focus.** The recommended order mirrors the official docs (Routing → Configuration → Request/Response → Database → Error Handling → Testing).
3. **Complete every practice task.** Each module ends with stretch goals to deepen understanding.
4. **Capture notes.** Append observations or gotchas directly inside each module file so this map grows with you.

## Module Overview

| Module File | Focus | Primary Files You'll Touch |
|-------------|-------|-----------------------------|
| `00-get-started.md` | Bootstrapping & Hello World walkthrough | `public/index.php`, `routes/web.php`
| `01-routing.md` | Route definitions, parameters, controllers | `routes/web.php`, `app/Http/Controllers/*`, `framework/Routing/*`
| `02-configuration.md` | App settings, env toggles, helpers | `config/*.php`, `.env.example`, `framework/Helpers/helpers.php`
| `03-request-response.md` | Request data, responses, headers | `framework/Http/*`, controller methods
| `04-database.md` | Connections, query builder, models | `config/database.php`, `framework/Database/*`, `app/Models/*`
| `05-error-handling.md` | Error & exception lifecycle, logging | `framework/Core/ErrorHandler.php`, `framework/Core/ExceptionHandler.php`, `storage/logs`
| `06-testing.md` | Automated checks & regression safety | `tests/*`, PHPUnit config

> 💡 **Tip:** Re-run completed modules later but raise the difficulty (e.g., add validation, middleware, caching) to keep leveling up.
