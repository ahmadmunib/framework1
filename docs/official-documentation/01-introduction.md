# Introduction

## What is DIS Framework?

DIS Framework is a lightweight, Laravel-inspired PHP framework designed for developers who need a modern development experience without external dependencies. It provides familiar patterns and conventions while maintaining complete control over your codebase.

## Key Features

- **Zero Dependencies** - Pure PHP implementation, no Composer packages required
- **PSR-4 Autoloading** - Automatic class loading with namespace support
- **Expressive Routing** - Laravel-style route definitions with parameters and groups
- **Request/Response Objects** - Clean HTTP abstraction layer
- **Query Builder** - Fluent database interface with prepared statements
- **Configuration Management** - Dot-notation config access
- **Error Handling** - Beautiful debug pages in development, clean errors in production

## Design Philosophy

1. **Simplicity** - Easy to understand, modify, and extend
2. **No Magic** - Transparent behavior, no hidden complexity
3. **Performance** - Optimized for speed without unnecessary overhead
4. **Security** - Built-in protection against common vulnerabilities
5. **Familiarity** - Laravel-like API for easy adoption

## Directory Structure

```
your-project/
├── app/                    # Application code
│   ├── Http/
│   │   ├── Controllers/    # HTTP controllers
│   │   └── Middleware/     # Custom middleware
│   └── Models/             # Data models
├── config/                 # Configuration files
│   ├── app.php
│   └── database.php
├── framework/              # Framework core (do not modify)
│   ├── Core/
│   ├── Database/
│   ├── Http/
│   ├── Routing/
│   └── Helpers/
├── public/                 # Web root
│   ├── index.php           # Front controller
│   └── .htaccess
├── routes/                 # Route definitions
│   ├── web.php
│   └── api.php
├── storage/                # Generated files
│   ├── cache/
│   ├── logs/
│   └── uploads/
└── tests/                  # Test files
```

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1+ |
| PDO Extension | Required for database |
| mbstring Extension | Recommended |
| Web Server | Apache/Nginx |

## Next Steps

- [Installation Guide](./02-installation.md)
- [Configuration](./03-configuration.md)
- [Your First Route](./04-routing.md)
