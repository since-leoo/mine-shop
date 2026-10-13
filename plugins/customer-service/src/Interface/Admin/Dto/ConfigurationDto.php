<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Interface\Admin\Dto;

use Plugin\CustomerService\Domain\Contract\ConfigurationInput;

final readonly class ConfigurationDto implements ConfigurationInput
{
    public function __construct(private array $snapshot, private int $revision, private bool $publishing) {}
    public function toArray(): array { return $this->snapshot; }
    public function expectedRevision(): int { return $this->revision; }
    public function publish(): bool { return $this->publishing; }
}
