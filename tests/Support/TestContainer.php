<?php

declare(strict_types=1);

namespace HyperfTests\Support;

use Psr\Container\ContainerInterface;

final class TestContainer implements ContainerInterface
{
    public function __construct(private array $entries = []) {}

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new \RuntimeException('Unbound test dependency: ' . $id);
        }
        return $this->entries[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    public function set(string $id, mixed $value): void
    {
        $this->entries[$id] = $value;
    }
}
