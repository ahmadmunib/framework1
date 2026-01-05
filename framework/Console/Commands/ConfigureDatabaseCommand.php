<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use PDO;
use PDOException;

/**
 * Configure database settings by writing .env and config/database.php values.
 *
 * Usage:
 *   php fx configure:database driver=mysql host=127.0.0.1 port=3306 database=app username=root password=secret test=true
 * Supported drivers: mysql, pgsql, sqlite
 */
class ConfigureDatabaseCommand implements CommandInterface
{
    protected const CONFIG_PATH = 'config/database.php';
    protected const ENV_PATH = '.env';

    public function getName(): string
    {
        return 'configure:database';
    }

    public function getDescription(): string
    {
        return 'Set DB connection in .env and config/database.php (with optional connection test)';
    }

    /**
     * @param string[] $arguments
     */
    public function handle(array $arguments): int
    {
        $options = $this->parseOptions($arguments);
        $driver = $options['driver'] ?? 'mysql';

        if (!$this->checkPdo($driver)) {
            return 1;
        }

        $configPath = base_path(self::CONFIG_PATH);
        if (!file_exists($configPath)) {
            fwrite(STDERR, "Config file not found: {$configPath}\n");
            return 1;
        }

        $config = require $configPath;
        if (!is_array($config)) {
            fwrite(STDERR, "Invalid config/database.php format.\n");
            return 1;
        }

        $config['default'] = $driver;
        $config['connections'][$driver] = $this->mergeConnection(
            $driver,
            $config['connections'][$driver] ?? [],
            $options
        );

        if (!$this->writeEnv($options)) {
            return 1;
        }

        if (!$this->writeConfig($configPath, $config)) {
            return 1;
        }

        if (!empty($options['test'])) {
            if (!$this->testConnection($driver, $options)) {
                return 1;
            }
        }

        fwrite(STDOUT, "Database configuration updated.\n");
        return 0;
    }

    /**
     * @param string[] $arguments
     * @return array<string, mixed>
     */
    protected function parseOptions(array $arguments): array
    {
        $options = [];
        foreach ($arguments as $arg) {
            if (!str_contains($arg, '=')) {
                if ($arg === 'test') {
                    $options['test'] = true;
                }
                continue;
            }
            [$key, $value] = explode('=', $arg, 2);
            $options[strtolower(trim($key))] = trim($value);
        }
        return $options;
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function mergeConnection(string $driver, array $existing, array $options): array
    {
        $merged = $existing;
        $merged['driver'] = $driver;

        $map = [
            'host' => 'host',
            'port' => 'port',
            'database' => 'database',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'charset',
            'collation' => 'collation',
            'schema' => 'schema',
            'prefix' => 'prefix',
        ];

        foreach ($map as $optionKey => $configKey) {
            if (isset($options[$optionKey])) {
                $merged[$configKey] = $options[$optionKey];
            }
        }

        // Defaults per driver
        if ($driver === 'mysql') {
            $merged['charset'] = $merged['charset'] ?? 'utf8mb4';
            $merged['collation'] = $merged['collation'] ?? 'utf8mb4_unicode_ci';
            $merged['port'] = $merged['port'] ?? 3306;
        } elseif ($driver === 'pgsql') {
            $merged['schema'] = $merged['schema'] ?? 'public';
            $merged['charset'] = $merged['charset'] ?? 'utf8';
            $merged['port'] = $merged['port'] ?? 5432;
        } elseif ($driver === 'sqlite') {
            $merged['database'] = $merged['database'] ?? base_path('database.sqlite');
        }

        return $merged;
    }

    protected function checkPdo(string $driver): bool
    {
        if (!extension_loaded('pdo')) {
            fwrite(STDERR, "PDO extension is not enabled.\n");
            return false;
        }

        $driverExt = match ($driver) {
            'mysql' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            default => null,
        };

        if ($driverExt === null) {
            fwrite(STDERR, "Unsupported driver: {$driver}. Use mysql, pgsql, or sqlite.\n");
            return false;
        }

        if (!extension_loaded($driverExt)) {
            fwrite(STDERR, "{$driverExt} extension is not enabled.\n");
            return false;
        }

        return true;
    }

    protected function testConnection(string $driver, array $options): bool
    {
        try {
            $dsn = $this->buildDsn($driver, $options);
            $username = $options['username'] ?? null;
            $password = $options['password'] ?? null;
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->query($driver === 'pgsql' ? 'SELECT 1' : 'SELECT 1');
            fwrite(STDOUT, "Connection test succeeded.\n");
            return true;
        } catch (PDOException $e) {
            fwrite(STDERR, "Connection test failed: " . $e->getMessage() . "\n");
            return false;
        }
    }

    protected function buildDsn(string $driver, array $options): string
    {
        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $options['host'] ?? 'localhost',
                $options['port'] ?? 3306,
                $options['database'] ?? '',
                $options['charset'] ?? 'utf8mb4'
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $options['host'] ?? 'localhost',
                $options['port'] ?? 5432,
                $options['database'] ?? ''
            ),
            'sqlite' => 'sqlite:' . ($options['database'] ?? base_path('database.sqlite')),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function writeEnv(array $options): bool
    {
        $envPath = base_path(self::ENV_PATH);
        $env = $this->readEnvFile($envPath);

        $env['DB_DRIVER'] = $options['driver'] ?? ($env['DB_DRIVER'] ?? 'mysql');
        $env['DB_HOST'] = $options['host'] ?? ($env['DB_HOST'] ?? 'localhost');
        $env['DB_PORT'] = $options['port'] ?? ($env['DB_PORT'] ?? ($env['DB_DRIVER'] === 'pgsql' ? 5432 : 3306));
        $env['DB_DATABASE'] = $options['database'] ?? ($env['DB_DATABASE'] ?? '');
        $env['DB_USERNAME'] = $options['username'] ?? ($env['DB_USERNAME'] ?? '');
        $env['DB_PASSWORD'] = $options['password'] ?? ($env['DB_PASSWORD'] ?? '');

        if ($env['DB_DRIVER'] === 'sqlite') {
            $env['DB_DATABASE'] = $options['database'] ?? ($env['DB_DATABASE'] ?? base_path('database.sqlite'));
        }

        $env = array_filter($env, static fn($v) => $v !== null);

        $lines = [];
        foreach ($env as $key => $value) {
            $lines[] = "{$key}={$value}";
        }

        if (file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL) === false) {
            fwrite(STDERR, "Failed to write .env file: {$envPath}\n");
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function writeConfig(string $path, array $config): bool
    {
        $export = var_export($config, true);
        $content = <<<PHP
<?php

/**
 * Database Configuration
 */

return {$export};

PHP;
        if (file_put_contents($path, $content) === false) {
            fwrite(STDERR, "Failed to write config: {$path}\n");
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function readEnvFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $env = [];
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }

        return $env;
    }
}
