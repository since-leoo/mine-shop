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

namespace App\Interface\Admin\Dto\Member;

use App\Domain\Member\Contract\MemberLevelInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 会员等级 DTO.
 */
class MemberLevelDto implements MemberLevelInput
{
    public ?int $id = null;

    #[Required]
    public string $name = '';

    #[Required]
    public int $level = 0;

    #[Required]
    public int $growth_value_min = 0;

    public ?int $growth_value_max = null;

    public ?float $discount_rate = null;

    public ?float $point_rate = null;

    /**
     * @var null|array<string, mixed>
     */
    public ?array $privileges = null;

    public ?string $icon = null;

    public ?string $color = null;

    #[Required]
    public string $status = 'active';

    public ?int $sort_order = null;

    public ?string $description = null;

    #[Required]
    public int $operator_id = 0;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getGrowthValueMin(): int
    {
        return $this->growth_value_min;
    }

    public function getGrowthValueMax(): ?int
    {
        return $this->growth_value_max;
    }

    public function getDiscountRate(): ?float
    {
        return $this->discount_rate;
    }

    public function getPointRate(): ?float
    {
        return $this->point_rate;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getPrivileges(): ?array
    {
        return $this->privileges;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOperatorId(): int
    {
        return $this->operator_id;
    }

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'level' => $this->level,
            'growth_value_min' => $this->growth_value_min,
            'growth_value_max' => $this->growth_value_max,
            'discount_rate' => $this->discount_rate,
            'point_rate' => $this->point_rate,
            'privileges' => $this->privileges,
            'icon' => $this->icon,
            'color' => $this->color,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'description' => $this->description,
        ];

        // 创建时添加 created_by
        if ($this->id === null) {
            $data['created_by'] = $this->operator_id;
        } else {
            // 更新时添加 updated_by
            $data['updated_by'] = $this->operator_id;
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }
}
