<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

/**
 * Configure app settings by writing .env and config/app.php values.
 *
 * Usage:
 *   php fx configure:app name="My App" env=local debug=true timezone=UTC url=http://localhost
 */
class ConfigureAppCommand implements CommandInterface
{
    protected const CONFIG_PATH = 'config/app.php';
    protected const ENV_PATH = '.env';

    public function getName(): string
    {
        return 'configure:app';
    }

    public function getDescription(): string
    {
        return 'Set app name, env, debug, timezone, and url in .env and config/app.php';
    }

    /**
     * @param string[] $arguments
     */
    public function handle(array $arguments): int
    {
        $options = $this->parseOptions($arguments);
        $configPath = base_path(self::CONFIG_PATH);

        if (!file_exists($configPath)) {
            fwrite(STDERR, "Config file not found: {$configPath}\n");
            return 1;
        }

        $config = require $configPath;
        if (!is_array($config)) {
            fwrite(STDERR, "Invalid config/app.php format.\n");
            return 1;
        }

        // Merge provided options
        $config['name'] = $options['name'] ?? ($config['name'] ?? 'DIS Framework');
        $config['env'] = $options['env'] ?? ($config['env'] ?? 'development');
        $config['debug'] = $options['debug'] ?? ($config['debug'] ?? true);
        $config['timezone'] = $options['timezone'] ?? ($config['timezone'] ?? 'UTC');
        $config['url'] = $options['url'] ?? ($config['url'] ?? 'http://localhost');
        $config['locale'] = $options['locale'] ?? ($config['locale'] ?? 'en');

        if (!$this->writeEnv($options)) {
            return 1;
        }

        if (!$this->writeConfig($configPath, $config)) {
            return 1;
        }

        fwrite(STDOUT, "App configuration updated.\n");
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
                continue;
            }

            [$key, $value] = explode('=', $arg, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            if ($key === 'debug') {
                $options['debug'] = $this->toBoolean($value);
                continue;
            }

            $map = [
                'name' => 'name',
                'env' => 'env',
                'timezone' => 'timezone',
                'url' => 'url',
                'locale' => 'locale',
            ];

            if (isset($map[$key])) {
                $options[$map[$key]] = $value;
            }
        }

        return $options;
    }

    protected function toBoolean(string $value): bool
    {
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
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
 * Application Configuration
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
     * @param array<string, mixed> $config
     */
    protected function writeEnv(array $config): bool
    {
        $envPath = base_path(self::ENV_PATH);
        $env = $this->readEnvFile($envPath);

        $env['APP_NAME'] = $config['name'] ?? ($env['APP_NAME'] ?? null);
        $env['APP_ENV'] = $config['env'] ?? ($env['APP_ENV'] ?? null);
        $env['APP_DEBUG'] = isset($config['debug']) ? ($config['debug'] ? 'true' : 'false') : ($env['APP_DEBUG'] ?? null);
        $env['APP_TIMEZONE'] = $config['timezone'] ?? ($env['APP_TIMEZONE'] ?? null);
        $env['APP_URL'] = $config['url'] ?? ($env['APP_URL'] ?? null);
        $env['APP_LOCALE'] = $config['locale'] ?? ($env['APP_LOCALE'] ?? null);

        // Remove nulls to avoid writing empty keys
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
