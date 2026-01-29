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
 * 秒杀活动实体.
 */
final class SeckillActivityEntity
{
    public function __construct(
        private int $id = 0,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $status = null,
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_enabled' => $this->isEnabled,
            'rules' => $this->rules,
            'remark' => $this->remark,
        ], static fn ($v) => $v !== null);
    }
}
