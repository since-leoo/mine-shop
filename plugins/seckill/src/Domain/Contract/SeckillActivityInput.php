<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Contract;

interface SeckillActivityInput
{
    public function getId(): int;

    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function getStatus(): ?string;

    public function getRules(): ?array;

    public function getRemark(): ?string;
}
