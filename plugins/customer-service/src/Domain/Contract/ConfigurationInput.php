<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

interface ConfigurationInput
{
    public function toArray(): array;
    public function expectedRevision(): int;
    public function publish(): bool;
}
