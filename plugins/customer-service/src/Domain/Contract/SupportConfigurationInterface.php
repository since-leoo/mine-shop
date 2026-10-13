<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

interface SupportConfigurationInterface
{
    /** @return array{revision:int, snapshot:array} */
    public function draft(): array;
    /** Published immutable configuration. Empty snapshot before first publication. */
    public function published(): array;
    /** Compare and swap; throws on a stale revision. */
    public function save(array $snapshot, int $expectedRevision, int $operatorId, bool $publish): array;
    public function history(): array;
    public function revision(int $revision): array;
}
