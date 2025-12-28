# DIS Framework Documentation

Welcome to the official documentation for **DIS Framework** - a Laravel-inspired PHP framework built without external dependencies.

## Table of Contents

1. [Introduction](./01-introduction.md)
2. [Installation](./02-installation.md)
3. [Configuration](./03-configuration.md)
4. [Routing](./04-routing.md)
5. [Request & Response](./05-request-response.md)
6. [Database](./06-database.md)
7. [Error Handling](./07-error-handling.md)

## Quick Start

```php
// Define a route
Router::get('/hello/{name}', function (Request $request, string $name) {
    return "Hello, {$name}!";
});
```

## Requirements

- PHP 8.1 or higher
- PDO extension (for database features)
- Apache with mod_rewrite or Nginx

## License

MIT License - See LICENSE file for details.
