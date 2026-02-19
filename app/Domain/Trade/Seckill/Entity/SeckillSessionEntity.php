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

use App\Domain\Trade\Seckill\Contract\SeckillSessionInput;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;
use App\Domain\Trade\Seckill\ValueObject\ProductStock;
use App\Domain\Trade\Seckill\ValueObject\SessionPeriod;
use App\Domain\Trade\Seckill\ValueObject\SessionRules;
use Carbon\Carbon;

/**
 * 秒杀场次实体.
 */
final class SeckillSessionEntity
{
    private int $id = 0;

    private int $activityId;

    private SessionPeriod $period;

    private SeckillStatus $status;

    private SessionRules $rules;

    private ProductStock $stock;

    private int $sortOrder;

    private bool $isEnabled;

    private ?string $remark = null;

    private ?Carbon $createdAt = null;

    private ?Carbon $updatedAt = null;

    public function __construct() {}

    /**
     * 从持久化数据重建实体.
     */
    public static function reconstitute(
        int $id,
        int $activityId,
        string $startTime,
        string $endTime,
        string $status,
        int $maxQuantityPerUser,
        int $totalQuantity,
        int $soldQuantity,
        int $sortOrder,
        bool $isEnabled,
        ?array $rulesData,
        ?string $remark,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ): self {
        $entity = new self();
        $entity->id = $id;
        $entity->activityId = $activityId;
        $entity->period = new SessionPeriod($startTime, $endTime);
        $entity->status = SeckillStatus::from($status);
        $rulesArray = array_merge(['max_quantity_per_user' => $maxQuantityPerUser, 'total_quantity' => $totalQuantity], $rulesData ?? []);
        $entity->rules = new SessionRules($rulesArray);
        $entity->stock = new ProductStock($totalQuantity, $soldQuantity);
        $entity->sortOrder = $sortOrder;
        $entity->isEnabled = $isEnabled;
        $entity->remark = $remark;
        $entity->createdAt = $createdAt;
        $entity->updatedAt = $updatedAt;
        return $entity;
    }

    /**
     * 从 DTO 创建实体.
     */
    public function create(SeckillSessionInput $dto): self
    {
        $this->activityId = $dto->getActivityId();
        $this->period = new SessionPeriod($dto->getStartTime(), $dto->getEndTime());
        $this->status = SeckillStatus::PENDING;
        $rulesData = array_merge(['max_quantity_per_user' => $dto->getMaxQuantityPerUser() ?? 1, 'total_quantity' => $dto->getTotalQuantity() ?? 0], $dto->getRules() ?? []);
        $this->rules = new SessionRules($rulesData);
        $this->stock = new ProductStock($dto->getTotalQuantity() ?? 0, 0);
        $this->sortOrder = $dto->getSortOrder() ?? 0;
        $this->isEnabled = true;
        $this->remark = $dto->getRemark();
        return $this;
    }

    /**
     * 从 DTO 更新实体.
     */
    public function update(SeckillSessionInput $dto): self
    {
        if ($dto->getStartTime() && $dto->getEndTime()) {
            $newPeriod = new SessionPeriod($dto->getStartTime(), $dto->getEndTime());
            if (! $newPeriod->equals($this->period)) {
                $this->period = $newPeriod;
            }
        }
        if ($dto->getMaxQuantityPerUser() !== null || $dto->getTotalQuantity() !== null || $dto->getRules()) {
            $rulesData = array_merge([
                'max_quantity_per_user' => $dto->getMaxQuantityPerUser() ?? $this->rules->getMaxQuantityPerUser(),
                'total_quantity' => $dto->getTotalQuantity() ?? $this->rules->getTotalQuantity(),
            ], $dto->getRules() ?? []);
            $this->rules = new SessionRules($rulesData);
        }
        if ($dto->getSortOrder() !== null) {
            $this->sortOrder = $dto->getSortOrder();
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

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getPeriod(): SessionPeriod
    {
        return $this->period;
    }

    public function getStatus(): SeckillStatus
    {
        return $this->status;
    }

    public function getRules(): SessionRules
    {
        return $this->rules;
    }

    public function getStock(): ProductStock
    {
        return $this->stock;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function canPurchase(): bool
    {
        return $this->isEnabled && ! $this->stock->isSoldOut() && $this->period->isActive();
    }

    /**
     * 判断场次是否可以编辑.
     *
     * 以下情况不允许编辑：
     * - 场次已激活或已结束
     * - 场次开始前 30 分钟内（缓存已开始预热）
     */
    public function canBeEdited(): bool
    {
        if ($this->status === SeckillStatus::ACTIVE || $this->status === SeckillStatus::ENDED) {
            return false;
        }

        // 开始前 30 分钟内禁止编辑（缓存预热期）
        if ($this->isWithinCacheWarmupPeriod()) {
            return false;
        }

        return true;
    }

    /**
     * 判断是否处于缓存预热期（开始前 30 分钟内）.
     */
    public function isWithinCacheWarmupPeriod(): bool
    {
        $startTime = $this->period->getStartTime();
        $now = Carbon::now();

        // 如果开始时间已过，不在预热期
        if ($startTime->lte($now)) {
            return false;
        }

        // 开始前 30 分钟内
        return $startTime->diffInMinutes($now) <= 30;
    }

    /**
     * 判断场次是否可以删除.
     *
     * 以下情况不允许删除：
     * - 场次已激活
     * - 已有销量
     * - 场次开始前 30 分钟内（缓存已开始预热）
     */
    public function canBeDeleted(): bool
    {
        if ($this->status === SeckillStatus::ACTIVE) {
            return false;
        }

        if ($this->stock->getSoldQuantity() > 0) {
            return false;
        }

        // 开始前 30 分钟内禁止删除（缓存预热期）
        if ($this->isWithinCacheWarmupPeriod()) {
            return false;
        }

        return true;
    }

    public function toggleEnabled(): self
    {
        $this->isEnabled = ! $this->isEnabled;
        return $this;
    }

    public function start(): self
    {
        if ($this->status !== SeckillStatus::PENDING) {
            throw new \DomainException('只有待开始的场次才能启动');
        }
        if (! $this->isEnabled) {
            throw new \DomainException('未启用的场次不能启动');
        }
        $this->status = SeckillStatus::ACTIVE;
        return $this;
    }

    public function end(): self
    {
        if ($this->status === SeckillStatus::ENDED) {
            throw new \DomainException('场次已经结束');
        }
        $this->status = SeckillStatus::ENDED;
        return $this;
    }

    public function soldOut(): self
    {
        $this->status = SeckillStatus::SOLD_OUT;
        return $this;
    }

    public function sell(int $quantity): self
    {
        $this->stock = $this->stock->sell($quantity);
        if ($this->stock->isSoldOut()) {
            $this->status = SeckillStatus::SOLD_OUT;
        }
        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'activity_id' => $this->activityId,
            'start_time' => $this->period->getStartTime()->toDateTimeString(),
            'end_time' => $this->period->getEndTime()->toDateTimeString(),
            'status' => $this->status->value,
            'max_quantity_per_user' => $this->rules->getMaxQuantityPerUser(),
            'total_quantity' => $this->stock->getQuantity(),
            'sold_quantity' => $this->stock->getSoldQuantity(),
            'sort_order' => $this->sortOrder, 'is_enabled' => $this->isEnabled,
            'rules' => $this->rules->toArray(), 'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
