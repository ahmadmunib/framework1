# DIS Framework

A lightweight, Laravel-inspired PHP framework with zero external dependencies.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Features

- 🚀 **Zero Dependencies** - Pure PHP, no Composer packages required
- 📦 **PSR-4 Autoloading** - Automatic class loading
- 🛣️ **Expressive Routing** - Laravel-style routes with parameters
- 📝 **Query Builder** - Fluent database interface
- 🔒 **Security** - Prepared statements, error handling
- ⚡ **Performance** - Lightweight and fast

## Quick Start

```bash
# Clone the repository
git clone https://github.com/your-org/dis-framework.git my-project
cd my-project

# Create storage directories
mkdir -p storage/logs storage/cache storage/uploads

# Start development server
php -S localhost:8000 -t public

# List framework CLI commands
php fx list
```

Visit `http://localhost:8000` to see your application running.

## Basic Usage

### Define Routes

```php
// routes/web.php
Router::get('/hello/{name}', function (Request $request, string $name) {
    return "Hello, {$name}!";
});

Router::get('/users', 'UserController@index');
```

### Database Queries

```php
use Framework\Database\Connection as DB;

// Query Builder
$users = DB::table('users')
    ->where('active', 1)
    ->orderBy('name')
    ->get();

// Insert
$id = DB::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

### Configuration

```php
// Access config values
$appName = config('app.name');
$dbHost = config('database.connections.mysql.host');
```

### Generate Controllers (Artisan-style)

```bash
# Create app/Http/Controllers/UserController.php
php fx make:controller UserController

# Support nested namespaces (creates app/Http/Controllers/Admin/UserController.php)
php fx make:controller Admin/UserController

# Scaffold routes files (routes/web.php and routes/api.php)
php fx make:routes
```

## Requirements

- PHP 8.1 or higher
- PDO extension
- Apache with mod_rewrite or Nginx

## Documentation

Full documentation is available in the [docs/official-documentation](docs/official-documentation/) directory:

- [Introduction](docs/official-documentation/01-introduction.md)
- [Installation](docs/official-documentation/02-installation.md)
- [Configuration](docs/official-documentation/03-configuration.md)
- [Routing](docs/official-documentation/04-routing.md)
- [Request & Response](docs/official-documentation/05-request-response.md)
- [Database](docs/official-documentation/06-database.md)
- [Error Handling](docs/official-documentation/07-error-handling.md)

## Directory Structure

```
├── app/                # Application code
│   ├── Http/Controllers/
│   └── Models/
├── config/             # Configuration files
├── framework/          # Framework core
├── public/             # Web root
├── routes/             # Route definitions
├── storage/            # Logs, cache, uploads
└── tests/              # Test files
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Author

Ahmad Munib - Technical Team Lead
