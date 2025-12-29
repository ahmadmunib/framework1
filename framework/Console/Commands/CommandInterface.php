<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

interface CommandInterface
{
    /**
     * Command name (e.g. make:controller)
     */
    public function getName(): string;

    /**
     * Short description displayed in list.
     */
    public function getDescription(): string;

    /**
     * Execute the command.
     *
     * @param string[] $arguments
     */
    public function handle(array $arguments): int;
}
