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

use App\Domain\Seckill\Contract\SeckillActivityInput;
use App\Domain\Seckill\Enum\SeckillStatus;
use App\Domain\Seckill\ValueObject\ActivityRules;
use Carbon\Carbon;

/**
 * 秒杀活动实体.
 *
 * 活动是场次的容器，本身不包含时间信息。
 * 时间信息在场次（Session）中定义。
 */
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

    /**
     * 从持久化数据重建.
     */
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

    /**
     * 创建行为方法：接收DTO，内部组装设置值.
     */
    public function create(SeckillActivityInput $dto): self
    {
        // Request层已验证title必填和长度，这里直接使用
        $this->title = $dto->getTitle() ?? '';
        $this->description = $dto->getDescription();
        $this->status = SeckillStatus::PENDING;
        $this->isEnabled = true;
        $this->rules = $dto->getRules() ? new ActivityRules($dto->getRules()) : ActivityRules::default();
        $this->remark = $dto->getRemark();

        return $this;
    }

    /**
     * 更新行为方法：接收DTO，内部组装设置值.
     */
    public function update(SeckillActivityInput $dto): self
    {
        // Request层已验证title格式，这里直接使用
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

    public function updateTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function updateDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): SeckillStatus
    {
        return $this->status;
    }

    public function updateStatus(SeckillStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function enable(): self
    {
        $this->isEnabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->isEnabled = false;
        return $this;
    }

    public function toggleEnabled(): self
    {
        $this->isEnabled = ! $this->isEnabled;
        return $this;
    }

    public function getRules(): ActivityRules
    {
        return $this->rules;
    }

    public function updateRules(ActivityRules $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function updateRemark(?string $remark): self
    {
        $this->remark = $remark;
        return $this;
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
     * 业务规则：检查活动是否可以启用.
     */
    public function canBeEnabled(): bool
    {
        // 已取消的活动不能启用
        if ($this->status === SeckillStatus::CANCELLED) {
            return false;
        }

        // 已结束的活动不能启用
        if ($this->status === SeckillStatus::ENDED) {
            return false;
        }

        return true;
    }

    /**
     * 业务规则：检查活动是否可以编辑.
     */
    public function canBeEdited(): bool
    {
        // 进行中的活动不能编辑基本信息
        if ($this->status === SeckillStatus::ACTIVE) {
            return false;
        }

        // 已结束的活动不能编辑
        if ($this->status === SeckillStatus::ENDED) {
            return false;
        }

        return true;
    }

    /**
     * 业务规则：检查活动是否可以删除.
     */
    public function canBeDeleted(): bool
    {
        // 进行中的活动不能删除
        if ($this->status === SeckillStatus::ACTIVE) {
            return false;
        }

        return true;
    }

    /**
     * 业务规则：检查活动是否可以取消.
     */
    public function canBeCancelled(): bool
    {
        // 已结束的活动不能取消
        if ($this->status === SeckillStatus::ENDED) {
            return false;
        }

        // 已取消的活动不能再次取消
        if ($this->status === SeckillStatus::CANCELLED) {
            return false;
        }

        return true;
    }

    /**
     * 取消活动.
     */
    public function cancel(): self
    {
        if (! $this->canBeCancelled()) {
            throw new \DomainException('当前活动状态不允许取消');
        }

        $this->status = SeckillStatus::CANCELLED;
        $this->isEnabled = false;

        return $this;
    }

    /**
     * 开始活动.
     */
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

    /**
     * 结束活动.
     */
    public function end(): self
    {
        if ($this->status === SeckillStatus::ENDED) {
            throw new \DomainException('活动已经结束');
        }

        $this->status = SeckillStatus::ENDED;

        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'is_enabled' => $this->isEnabled,
            'rules' => $this->rules->toArray(),
            'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
