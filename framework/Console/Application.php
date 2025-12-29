<?php

declare(strict_types=1);

namespace Framework\Console;

use Framework\Console\Commands\CommandInterface;

/**
 * Minimal console application similar to Laravel's Artisan.
 */
class Application
{
    /**
     * @var array<string, CommandInterface>
     */
    protected array $commands = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    /**
     * Register the built-in commands.
     */
    protected function registerDefaultCommands(): void
    {
        $this->register(new Commands\MakeControllerCommand());
    }

    /**
     * Register a command instance.
     */
    public function register(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Run a command by name.
     *
     * @param string $name
     * @param string[] $arguments
     */
    public function run(string $name, array $arguments = []): int
    {
        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Command [{$name}] not found.\n");
            return 1;
        }

        return $this->commands[$name]->handle($arguments);
    }

    /**
     * Render the list of available commands.
     */
    public function listCommands(): string
    {
        $lines = ["Available commands:"];

        foreach ($this->commands as $command) {
            $lines[] = sprintf(
                "  %-25s %s",
                $command->getName(),
                $command->getDescription()
            );
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
