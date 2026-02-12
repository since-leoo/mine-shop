<?php

declare(strict_types=1);

namespace App\Interface\Admin\Dto\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillActivityInput;

final class SeckillActivityDto implements SeckillActivityInput
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $status = null;

    public ?array $rules = null;

    public ?string $remark = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }
}
