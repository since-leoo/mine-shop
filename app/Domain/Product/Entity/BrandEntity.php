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

    private string $name = '';

    private ?string $logo = null;

    private ?string $description = null;

    private ?string $website = null;

    private int $sort = 0;

    private string $status = 'active';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id = 0): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name = ''): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo = null): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): self
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
        $this->website = $website;
        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort = 0): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status = 'active'): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === BrandStatus::ACTIVE->value;
    }

    /**
     * 转换为数组（用于持久化）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->getName() ?: null,
            'logo' => $this->getLogo(),
            'description' => $this->getDescription(),
            'website' => $this->getWebsite(),
            'sort' => $this->getSort(),
            'status' => $this->getStatus(),
        ], static fn ($v) => $v !== null);
    }
}
