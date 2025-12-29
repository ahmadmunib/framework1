<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

/**
 * Generates a controller file similar to Laravel's make:controller.
 */
class MakeControllerCommand implements CommandInterface
{
    protected const BASE_NAMESPACE = 'App\\Http\\Controllers';
    protected const BASE_PATH = 'Http/Controllers';

    public function getName(): string
    {
        return 'make:controller';
    }

    public function getDescription(): string
    {
        return 'Create a new controller class';
    }

    /**
     * @param string[] $arguments
     */
    public function handle(array $arguments): int
    {
        if (empty($arguments)) {
            fwrite(STDERR, "Missing controller name.\nUsage: php fx make:controller UserController\n");
            return 1;
        }

        $segments = $this->normalizeName($arguments[0]);

        if (empty($segments)) {
            fwrite(STDERR, "Invalid controller name provided.\n");
            return 1;
        }

        $className = $this->determineClassName(array_pop($segments));
        $namespace = $this->determineNamespace($segments);

        $directory = app_path(self::BASE_PATH . ($segments ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments) : ''));
        $filePath = $directory . DIRECTORY_SEPARATOR . $className . '.php';

        if (file_exists($filePath)) {
            fwrite(STDERR, "Controller already exists at {$filePath}.\n");
            return 1;
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            fwrite(STDERR, "Unable to create directory: {$directory}\n");
            return 1;
        }

        $stub = $this->buildStub($namespace, $className);

        if (file_put_contents($filePath, $stub) === false) {
            fwrite(STDERR, "Failed to write controller file.\n");
            return 1;
        }

        fwrite(STDOUT, "Controller created successfully: {$filePath}\n");
        return 0;
    }

    /**
     * Normalize the raw name into namespace segments.
     *
     * @return string[]
     */
    protected function normalizeName(string $name): array
    {
        $normalized = str_replace(['\\', '/'], '/', trim($name));
        $segments = array_values(array_filter(explode('/', $normalized)));

        if (count($segments) >= 3
            && $segments[0] === 'App'
            && $segments[1] === 'Http'
            && $segments[2] === 'Controllers') {
            $segments = array_slice($segments, 3);
        }

        return $segments;
    }

    protected function determineClassName(string $name): string
    {
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        return $name;
    }

    /**
     * Build full namespace for the controller.
     *
     * @param string[] $segments
     */
    protected function determineNamespace(array $segments): string
    {
        if (empty($segments)) {
            return self::BASE_NAMESPACE;
        }

        return self::BASE_NAMESPACE . '\\' . implode('\\', $segments);
    }

    protected function buildStub(string $namespace, string $className): string
    {
        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    public function index()
    {
        //
    }
}

PHP;

        return $stub;
    }
}
