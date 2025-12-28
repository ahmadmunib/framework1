# Installation

## Requirements

Before installing DIS Framework, ensure your system meets these requirements:

- **PHP 8.1** or higher
- **PDO PHP Extension** (for database functionality)
- **Apache** with `mod_rewrite` enabled, or **Nginx**

## Installation Methods

### Method 1: Git Clone (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-org/dis-framework.git my-project

# Navigate to project
cd my-project

# Create required directories
mkdir -p storage/logs storage/cache storage/uploads

# Set permissions (Linux/Mac)
chmod -R 775 storage
```

### Method 2: Download ZIP

1. Download the latest release from GitHub
2. Extract to your desired location
3. Create the storage directories manually

### Method 3: Composer (if published)

```bash
composer create-project dis/framework my-project
```

## Web Server Configuration

### Apache

The framework includes a `.htaccess` file in the `public/` directory. Ensure:

1. `mod_rewrite` is enabled:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. Your virtual host allows `.htaccess` overrides:
   ```apache
   <VirtualHost *:80>
       ServerName myapp.local
       DocumentRoot /path/to/my-project/public
       
       <Directory /path/to/my-project/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

### Nginx

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/my-project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### PHP Built-in Server (Development Only)

For quick testing, use PHP's built-in server:

```bash
cd my-project
php -S localhost:8000 -t public
```

Then visit: `http://localhost:8000`

## Directory Permissions

Ensure the `storage/` directory is writable by the web server:

```bash
# Linux/Mac
chmod -R 775 storage
chown -R www-data:www-data storage  # Adjust user as needed

# Or for development
chmod -R 777 storage
```

## Verify Installation

1. Start your web server
2. Visit your application URL
3. You should see the welcome page: "🚀 DIS Framework - Your custom PHP framework is running!"

### Test API Endpoint

```bash
curl http://localhost:8000/api/status
```

Expected response:
```json
{
    "status": "ok",
    "framework": "DIS Framework",
    "php_version": "8.1.0",
    "timestamp": "2025-12-28T22:00:00+00:00"
}
```

## Troubleshooting

### 404 Not Found

- Ensure `mod_rewrite` is enabled (Apache)
- Check that `.htaccess` file exists in `public/`
- Verify `AllowOverride All` is set in Apache config

### 500 Internal Server Error

- Check `storage/logs/` for error logs
- Ensure PHP version is 8.1+
- Verify file permissions on `storage/`

### Blank Page

- Enable PHP error display temporarily:
  ```php
  // In public/index.php (temporarily)
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check PHP error logs

## Next Steps

- [Configure your application](./03-configuration.md)
- [Define your first routes](./04-routing.md)
