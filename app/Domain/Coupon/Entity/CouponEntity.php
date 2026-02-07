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

namespace App\Domain\Coupon\Entity;

use App\Domain\Coupon\Contract\CouponInput;
use Carbon\Carbon;

/**
 * 优惠券实体.
 */
final class CouponEntity
{
    private int $id;

    private ?string $name;

    private ?string $type;

    private ?int $value;

    private ?int $minAmount;

    private ?int $totalQuantity;

    private ?int $usedQuantity;

    private ?int $perUserLimit;

    private ?string $startTime;

    private ?string $endTime;

    private ?string $status;

    private ?string $description;

    public function __construct()
    {
        $this->id = 0;
        $this->name = null;
        $this->type = null;
        $this->value = null;
        $this->minAmount = null;
        $this->totalQuantity = null;
        $this->usedQuantity = 0;
        $this->perUserLimit = null;
        $this->startTime = null;
        $this->endTime = null;
        $this->status = 'active';
        $this->description = null;
    }

    public function create(CouponInput $dto): self
    {
        $this->name = $dto->getName();
        $this->type = $dto->getType();
        $this->value = $dto->getValue();
        $this->minAmount = $dto->getMinAmount();
        $this->totalQuantity = $dto->getTotalQuantity();
        $this->usedQuantity = 0;
        $this->perUserLimit = $dto->getPerUserLimit();
        $this->startTime = $dto->getStartTime();
        $this->endTime = $dto->getEndTime();
        $this->status = 'active';
        $this->description = $dto->getDescription();

        // Validate time window
        $this->ensureTimeWindowIsValid();

        return $this;
    }

    public function update(CouponInput $dto): self
    {
        if ($dto->getName() !== null) {
            $this->name = $dto->getName();
        }

        if ($dto->getType() !== null) {
            $this->type = $dto->getType();
        }

        if ($dto->getValue() !== null) {
            $this->value = $dto->getValue();
        }

        if ($dto->getMinAmount() !== null) {
            $this->minAmount = $dto->getMinAmount();
        }

        if ($dto->getTotalQuantity() !== null) {
            $this->totalQuantity = $dto->getTotalQuantity();
        }

        if ($dto->getPerUserLimit() !== null) {
            $this->perUserLimit = $dto->getPerUserLimit();
        }

        if ($dto->getStartTime() !== null) {
            $this->startTime = $dto->getStartTime();
        }

        if ($dto->getEndTime() !== null) {
            $this->endTime = $dto->getEndTime();
        }

        if ($dto->getStatus() !== null) {
            $this->status = $dto->getStatus();
        }

        if ($dto->getDescription() !== null) {
            $this->description = $dto->getDescription();
        }

        // Validate time window if times were updated
        if ($dto->getStartTime() !== null || $dto->getEndTime() !== null) {
            $this->ensureTimeWindowIsValid();
        }

        return $this;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setValue(?int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function setMinAmount(?int $minAmount): self
    {
        $this->minAmount = $minAmount;
        return $this;
    }

    public function setTotalQuantity(?int $totalQuantity): self
    {
        $this->totalQuantity = $totalQuantity;
        return $this;
    }

    public function setUsedQuantity(?int $usedQuantity): self
    {
        $this->usedQuantity = $usedQuantity;
        return $this;
    }

    public function setPerUserLimit(?int $perUserLimit): self
    {
        $this->perUserLimit = $perUserLimit;
        return $this;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function defineTimeWindow(?string $startTime, ?string $endTime): self
    {
        if ($startTime && $endTime) {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);
            if ($start->gte($end)) {
                throw new \InvalidArgumentException('优惠券生效时间不合法');
            }
        }

        $this->startTime = $startTime;
        $this->endTime = $endTime;
        return $this;
    }

    public function ensureTimeWindowIsValid(): self
    {
        return $this->defineTimeWindow($this->startTime, $this->endTime);
    }

    public function activate(): self
    {
        $this->status = 'active';
        return $this;
    }

    public function deactivate(): self
    {
        $this->status = 'inactive';
        return $this;
    }

    public function toggleStatus(): self
    {
        $this->status = $this->status === 'active' ? 'inactive' : 'active';
        return $this;
    }

    public function assertActive(): self
    {
        if ($this->status !== 'active') {
            throw new \RuntimeException('优惠券未启用');
        }
        return $this;
    }

    public function assertEffectiveAt(Carbon $now): self
    {
        $start = $this->getStartTimeCarbon();
        $end = $this->getEndTimeCarbon();

        if ($start && $now->lt($start)) {
            throw new \RuntimeException('优惠券未开始');
        }

        if ($end && $now->gt($end)) {
            throw new \RuntimeException('优惠券已过期');
        }

        return $this;
    }

    public function assertAvailableStock(int $availableQuantity, int $requestQuantity): self
    {
        if ($availableQuantity < $requestQuantity) {
            throw new \RuntimeException('优惠券库存不足');
        }
        return $this;
    }

    public function canMemberReceive(int $currentCount): bool
    {
        if ($this->perUserLimit === null) {
            return true;
        }

        return $currentCount < $this->perUserLimit;
    }

    public function resolveExpireAt(?string $customExpireAt, Carbon $now): Carbon
    {
        $custom = $customExpireAt ? Carbon::parse($customExpireAt) : null;
        $end = $this->getEndTimeCarbon();

        $deadline = match (true) {
            $custom && $end => $custom->lt($end) ? $custom : $end,
            $custom => $custom,
            $end => $end,
            default => $now->copy()->addDays(30),
        };

        if ($deadline->lte($now)) {
            throw new \InvalidArgumentException('过期时间无效');
        }

        return $deadline;
    }

    public function setStartTime(?string $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function setEndTime(?string $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getStartTimeCarbon(): ?Carbon
    {
        return $this->startTime ? Carbon::parse($this->startTime) : null;
    }

    public function getEndTimeCarbon(): ?Carbon
    {
        return $this->endTime ? Carbon::parse($this->endTime) : null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function getMinAmount(): ?int
    {
        return $this->minAmount;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function getUsedQuantity(): ?int
    {
        return $this->usedQuantity;
    }

    public function getPerUserLimit(): ?int
    {
        return $this->perUserLimit;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'min_amount' => $this->minAmount,
            'total_quantity' => $this->totalQuantity,
            'used_quantity' => $this->usedQuantity,
            'per_user_limit' => $this->perUserLimit,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => $this->status,
            'description' => $this->description,
        ], static fn ($value) => $value !== null);
    }
}
