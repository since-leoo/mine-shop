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

namespace App\Domain\Seckill\Entity;

/**
 * 秒杀场次实体.
 */
final class SeckillSessionEntity
{
    public function __construct(
        private int $id = 0,
        private ?int $activityId = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
        private ?string $status = null,
        private ?int $maxQuantityPerUser = null,
        private ?int $totalQuantity = null,
        private ?int $soldQuantity = null,
        private ?int $sortOrder = null,
        private ?bool $isEnabled = null,
        private ?array $rules = null,
        private ?string $remark = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getActivityId(): ?int
    {
        return $this->activityId;
    }

    public function setActivityId(?int $activityId): self
    {
        $this->activityId = $activityId;
        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMaxQuantityPerUser(): ?int
    {
        return $this->maxQuantityPerUser;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function getSoldQuantity(): ?int
    {
        return $this->soldQuantity;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(?bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'activity_id' => $this->activityId,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => $this->status,
            'max_quantity_per_user' => $this->maxQuantityPerUser,
            'total_quantity' => $this->totalQuantity,
            'sold_quantity' => $this->soldQuantity,
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
            'rules' => $this->rules,
            'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
