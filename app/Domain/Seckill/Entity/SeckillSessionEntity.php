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

use App\Domain\Seckill\Contract\SeckillSessionInput;
use App\Domain\Seckill\Enum\SeckillStatus;
use App\Domain\Seckill\ValueObject\ProductStock;
use App\Domain\Seckill\ValueObject\SessionPeriod;
use App\Domain\Seckill\ValueObject\SessionRules;
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
     * 从持久化数据重建.
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

        // 合并规则数据
        $rulesArray = array_merge([
            'max_quantity_per_user' => $maxQuantityPerUser,
            'total_quantity' => $totalQuantity,
        ], $rulesData ?? []);

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
     * 创建行为方法：接收DTO，内部组装设置值.
     */
    public function create(SeckillSessionInput $dto): self
    {
        // Request层已验证activity_id必填，这里直接使用
        $this->activityId = $dto->getActivityId();

        // Request层已验证时间格式和范围，这里直接创建值对象
        $this->period = new SessionPeriod($dto->getStartTime(), $dto->getEndTime());
        $this->status = SeckillStatus::PENDING;

        $rulesData = array_merge([
            'max_quantity_per_user' => $dto->getMaxQuantityPerUser() ?? 1,
            'total_quantity' => $dto->getTotalQuantity() ?? 0,
        ], $dto->getRules() ?? []);

        $this->rules = new SessionRules($rulesData);
        $this->stock = new ProductStock($dto->getTotalQuantity() ?? 0, 0);
        $this->sortOrder = $dto->getSortOrder() ?? 0;
        $this->isEnabled = true;
        $this->remark = $dto->getRemark();

        return $this;
    }

    /**
     * 更新行为方法：接收DTO，内部组装设置值.
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

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    /**
     * 计算动态状态.
     */
    public function calculateDynamicStatus(): SeckillStatus
    {
        if (! $this->isEnabled) {
            return SeckillStatus::CANCELLED;
        }

        if ($this->stock->isSoldOut()) {
            return SeckillStatus::SOLD_OUT;
        }

        if ($this->period->isPending()) {
            return SeckillStatus::PENDING;
        }

        if ($this->period->isEnded()) {
            return SeckillStatus::ENDED;
        }

        if ($this->period->isActive()) {
            return SeckillStatus::ACTIVE;
        }

        return $this->status;
    }

    /**
     * 业务规则：检查场次是否可以购买.
     */
    public function canPurchase(): bool
    {
        if (! $this->isEnabled) {
            return false;
        }

        if ($this->stock->isSoldOut()) {
            return false;
        }

        if (! $this->period->isActive()) {
            return false;
        }

        return true;
    }

    /**
     * 业务规则：检查场次是否可以编辑.
     */
    public function canBeEdited(): bool
    {
        // 进行中的场次不能编辑
        if ($this->status === SeckillStatus::ACTIVE) {
            return false;
        }

        // 已结束的场次不能编辑
        if ($this->status === SeckillStatus::ENDED) {
            return false;
        }

        return true;
    }

    /**
     * 业务规则：检查场次是否可以删除.
     */
    public function canBeDeleted(): bool
    {
        // 进行中的场次不能删除
        if ($this->status === SeckillStatus::ACTIVE) {
            return false;
        }

        // 有销量的场次不能删除
        if ($this->stock->getSoldQuantity() > 0) {
            return false;
        }

        return true;
    }

    /**
     * 启用场次.
     */
    public function enable(): self
    {
        $this->isEnabled = true;
        return $this;
    }

    /**
     * 禁用场次.
     */
    public function disable(): self
    {
        $this->isEnabled = false;
        return $this;
    }

    /**
     * 切换启用状态.
     */
    public function toggleEnabled(): self
    {
        $this->isEnabled = ! $this->isEnabled;
        return $this;
    }

    /**
     * 开始场次.
     */
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

    /**
     * 结束场次.
     */
    public function end(): self
    {
        if ($this->status === SeckillStatus::ENDED) {
            throw new \DomainException('场次已经结束');
        }

        $this->status = SeckillStatus::ENDED;
        return $this;
    }

    /**
     * 售罄.
     */
    public function soldOut(): self
    {
        $this->status = SeckillStatus::SOLD_OUT;
        return $this;
    }

    /**
     * 扣减库存.
     */
    public function sell(int $quantity): self
    {
        $this->stock = $this->stock->sell($quantity);

        // 如果售罄，更新状态
        if ($this->stock->isSoldOut()) {
            $this->status = SeckillStatus::SOLD_OUT;
        }

        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     */
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
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
            'rules' => $this->rules->toArray(),
            'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
