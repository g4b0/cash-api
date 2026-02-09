<?php

namespace App\Command;

use PDO;

abstract class AbstractCommand
{
    protected PDO $db;
    protected array $args;
    protected $stdout;
    protected $stderr;

    public function __construct(PDO $db, array $args = [], $stdout = null, $stderr = null)
    {
        $this->db = $db;
        $this->args = $args;
        $this->stdout = $stdout ?? STDOUT;
        $this->stderr = $stderr ?? STDERR;
    }

    abstract public function execute(): int;

    protected function output(string $message): void
    {
        fwrite($this->stdout, $message . PHP_EOL);
    }

    protected function error(string $message): void
    {
        fwrite($this->stderr, "ERROR: $message" . PHP_EOL);
    }

    protected function success(string $message): void
    {
        fwrite($this->stdout, "✓ $message" . PHP_EOL);
    }

    protected function info(string $message): void
    {
        fwrite($this->stdout, "ℹ $message" . PHP_EOL);
    }
}
