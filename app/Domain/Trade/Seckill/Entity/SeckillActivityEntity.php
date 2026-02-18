<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Contract\SeckillActivityInput;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Domain\Trade\Seckill\ValueObject\ActivityRules;
use Carbon\Carbon;

final class SeckillActivityEntity
{
    private int $id = 0;

    private string $title;

    private ?string $description = null;

    private SeckillStatus $status;

    private bool $isEnabled;

    private ActivityRules $rules;

    private ?string $remark = null;

    private ?Carbon $createdAt = null;

    private ?Carbon $updatedAt = null;

    public function __construct() {}

    public static function reconstitute(
        int $id,
        string $title,
        ?string $description,
        string $status,
        bool $isEnabled,
        ?array $rulesData,
        ?string $remark,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ): self {
        $entity = new self();
        $entity->id = $id;
        $entity->title = $title;
        $entity->description = $description;
        $entity->status = SeckillStatus::from($status);
        $entity->isEnabled = $isEnabled;
        $entity->rules = $rulesData ? new ActivityRules($rulesData) : ActivityRules::default();
        $entity->remark = $remark;
        $entity->createdAt = $createdAt;
        $entity->updatedAt = $updatedAt;
        return $entity;
    }

    public function create(SeckillActivityInput $dto): self
    {
        $this->title = $dto->getTitle() ?? '';
        $this->description = $dto->getDescription();
        $this->status = SeckillStatus::PENDING;
        $this->isEnabled = true;
        $this->rules = $dto->getRules() ? new ActivityRules($dto->getRules()) : ActivityRules::default();
        $this->remark = $dto->getRemark();
        return $this;
    }

    public function update(SeckillActivityInput $dto): self
    {
        if ($dto->getTitle()) {
            $this->title = $dto->getTitle();
        }
        if ($dto->getDescription() !== null) {
            $this->description = $dto->getDescription();
        }
        if ($dto->getRules()) {
            $this->rules = new ActivityRules($dto->getRules());
        }
        if ($dto->getRemark() !== null) {
            $this->remark = $dto->getRemark();
        }
        if ($dto->getStatus()) {
            $this->status = SeckillStatus::from($dto->getStatus());
        }
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): SeckillStatus
    {
        return $this->status;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getRules(): ActivityRules
    {
        return $this->rules;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function toggleEnabled(): self
    {
        $this->isEnabled = ! $this->isEnabled;
        return $this;
    }

    public function canBeEnabled(): bool
    {
        return $this->status !== SeckillStatus::CANCELLED && $this->status !== SeckillStatus::ENDED;
    }

    public function canBeEdited(): bool
    {
        return $this->status !== SeckillStatus::ACTIVE && $this->status !== SeckillStatus::ENDED;
    }

    public function canBeDeleted(): bool
    {
        return $this->status !== SeckillStatus::ACTIVE;
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== SeckillStatus::ENDED && $this->status !== SeckillStatus::CANCELLED;
    }

    public function cancel(): self
    {
        if (! $this->canBeCancelled()) {
            throw new \DomainException('当前活动状态不允许取消');
        }
        $this->status = SeckillStatus::CANCELLED;
        $this->isEnabled = false;
        return $this;
    }

    public function start(): self
    {
        if ($this->status !== SeckillStatus::PENDING) {
            throw new \DomainException('只有待开始的活动才能启动');
        }
        if (! $this->isEnabled) {
            throw new \DomainException('未启用的活动不能启动');
        }
        $this->status = SeckillStatus::ACTIVE;
        return $this;
    }

    public function end(): self
    {
        if ($this->status === SeckillStatus::ENDED) {
            throw new \DomainException('活动已经结束');
        }
        $this->status = SeckillStatus::ENDED;
        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title, 'description' => $this->description,
            'status' => $this->status->value, 'is_enabled' => $this->isEnabled,
            'rules' => $this->rules->toArray(), 'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
