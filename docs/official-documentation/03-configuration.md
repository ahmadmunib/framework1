# Configuration

## Overview

DIS Framework uses PHP array files for configuration, stored in the `config/` directory. Configuration values are accessed using dot notation.

## Configuration Files

### config/app.php

Main application settings:

```php
<?php

return [
    // Application name
    'name' => 'My Application',
    
    // Environment: local, development, staging, production
    'env' => 'development',
    
    // Debug mode (disable in production!)
    'debug' => true,
    
    // Application URL
    'url' => 'http://localhost',
    
    // Timezone
    'timezone' => 'UTC',
    
    // Locale
    'locale' => 'en',
];
```

### config/database.php

Database connection settings:

```php
<?php

return [
    // Default connection
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'my_database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => '/path/to/database.sqlite',
        ],
    ],
];
```

## Accessing Configuration

### Using the Config Class

```php
use Framework\Core\Config;

// Get a value
$appName = Config::get('app.name');

// Get with default value
$timezone = Config::get('app.timezone', 'UTC');

// Get nested values
$dbHost = Config::get('database.connections.mysql.host');

// Check if key exists
if (Config::has('app.debug')) {
    // ...
}

// Set a value at runtime
Config::set('app.debug', false);

// Get all configuration
$allConfig = Config::all();
```

### Using the Helper Function

```php
// Get value
$name = config('app.name');

// Get with default
$debug = config('app.debug', false);

// Set multiple values
config([
    'app.debug' => false,
    'app.timezone' => 'America/New_York',
]);

// Get all config
$all = config();
```

## Environment Variables

Use the `env()` helper to read environment variables:

```php
// In config/app.php
return [
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
];
```

### Setting Environment Variables

**Method 1: Server Configuration**
```apache
# Apache .htaccess or vhost
SetEnv APP_DEBUG true
SetEnv APP_URL https://myapp.com
```

**Method 2: PHP-FPM**
```ini
; In php-fpm pool config
env[APP_DEBUG] = true
env[APP_URL] = https://myapp.com
```

**Method 3: Export (CLI)**
```bash
export APP_DEBUG=true
export APP_URL=https://myapp.com
php public/index.php
```

## Environment-Specific Configuration

Create environment-specific config by checking the environment:

```php
<?php
// config/app.php

$env = getenv('APP_ENV') ?: 'development';

$config = [
    'name' => 'My App',
    'debug' => false,
];

// Environment overrides
if ($env === 'development') {
    $config['debug'] = true;
}

return $config;
```

## Path Helpers

The framework provides path helper functions:

```php
// Base application path
base_path();                    // /var/www/my-project
base_path('config/app.php');    // /var/www/my-project/config/app.php

// Configuration directory
config_path();                  // /var/www/my-project/config
config_path('database.php');    // /var/www/my-project/config/database.php

// Storage directory
storage_path();                 // /var/www/my-project/storage
storage_path('logs/app.log');   // /var/www/my-project/storage/logs/app.log

// Public directory
public_path();                  // /var/www/my-project/public
public_path('assets/app.css');  // /var/www/my-project/public/assets/app.css

// Application directory
app_path();                     // /var/www/my-project/app
app_path('Models/User.php');    // /var/www/my-project/app/Models/User.php
```

## Best Practices

1. **Never commit sensitive data** - Use environment variables for passwords, API keys
2. **Disable debug in production** - Set `app.debug` to `false`
3. **Use appropriate timezones** - Set `app.timezone` correctly
4. **Validate configuration** - Check required values on boot if needed

## Next Steps

- [Define routes](./04-routing.md)
- [Handle requests](./05-request-response.md)
