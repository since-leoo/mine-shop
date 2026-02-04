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

namespace App\Domain\Product\Entity;

use App\Domain\Product\Enum\BrandStatus;

/**
 * 品牌实体.
 */
final class BrandEntity
{
    private int $id = 0;

    private ?string $name = null;

    private ?string $logo = null;

    private ?string $description = null;

    private ?string $website = null;

    private ?int $sort = null;

    private string $status = BrandStatus::ACTIVE->value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = ''): self
    {
        return $this->rename($name);
    }

    public function rename(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new \DomainException('品牌名称不能为空');
        }
        $this->name = $name;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo = null): self
    {
        return $this->changeLogo($logo);
    }

    public function changeLogo(?string $logo): self
    {
        $this->logo = $logo ?: null;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): self
    {
        return $this->describe($description);
    }

    public function describe(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website = null): self
    {
        return $this->changeWebsite($website);
    }

    public function changeWebsite(?string $website): self
    {
        $website = $website !== null ? trim($website) : null;
        $this->website = $website ?: null;
        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort = 0): self
    {
        return $this->applySort($sort);
    }

    public function applySort(int $sort): self
    {
        $this->sort = max(0, $sort);
        return $this;
    }

    public function needsSort(): bool
    {
        return $this->sort === null || $this->sort <= 0;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status = 'active'): self
    {
        return $this->changeStatus($status);
    }

    public function changeStatus(string $status): self
    {
        $values = array_map(static fn (BrandStatus $case) => $case->value, BrandStatus::cases());
        if (! \in_array($status, $values, true)) {
            throw new \DomainException('品牌状态值无效');
        }
        $this->status = $status;
        return $this;
    }

    public function activate(): self
    {
        return $this->changeStatus(BrandStatus::ACTIVE->value);
    }

    public function deactivate(): self
    {
        return $this->changeStatus(BrandStatus::INACTIVE->value);
    }

    public function isActive(): bool
    {
        return $this->status === BrandStatus::ACTIVE->value;
    }

    public function ensureCanPersist(bool $isCreate = false): void
    {
        if ($isCreate && $this->name === null) {
            throw new \DomainException('品牌名称不能为空');
        }
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->getName(),
            'logo' => $this->getLogo(),
            'description' => $this->getDescription(),
            'website' => $this->getWebsite(),
            'sort' => $this->getSort(),
            'status' => $this->getStatus(),
        ], static fn ($v) => $v !== null);
    }
}
