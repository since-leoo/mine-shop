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

namespace App\Domain\Member\Entity;

/**
 * 会员等级实体.
 */
final class MemberLevelEntity
{
    private int $id = 0;

    private ?string $name = null;

    private ?int $level = null;

    private ?int $growthMin = null;

    private ?int $growthMax = null;

    private ?float $discountRate = null;

    private ?float $pointRate = null;

    /** @var null|array<string, mixed> */
    private ?array $privileges = null;

    private ?string $icon = null;

    private ?string $color = null;

    private ?string $status = null;

    private ?int $sortOrder = null;

    private ?string $description = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    public function getGrowthMin(): ?int
    {
        return $this->growthMin;
    }

    public function setGrowthMin(?int $value): void
    {
        $this->growthMin = $value;
    }

    public function getGrowthMax(): ?int
    {
        return $this->growthMax;
    }

    public function setGrowthMax(?int $value): void
    {
        $this->growthMax = $value;
    }

    public function getDiscountRate(): ?float
    {
        return $this->discountRate;
    }

    public function setDiscountRate(?float $rate): void
    {
        $this->discountRate = $rate;
    }

    public function getPointRate(): ?float
    {
        return $this->pointRate;
    }

    public function setPointRate(?float $rate): void
    {
        $this->pointRate = $rate;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getPrivileges(): ?array
    {
        return $this->privileges;
    }

    /**
     * @param null|array<string, mixed> $privileges
     */
    public function setPrivileges(?array $privileges): void
    {
        $this->privileges = $privileges;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'growth_value_min' => $this->growthMin,
            'growth_value_max' => $this->growthMax,
            'discount_rate' => $this->discountRate,
            'point_rate' => $this->pointRate,
            'privileges' => $this->privileges,
            'icon' => $this->icon,
            'color' => $this->color,
            'status' => $this->status,
            'sort_order' => $this->sortOrder,
            'description' => $this->description,
        ];
    }
}
