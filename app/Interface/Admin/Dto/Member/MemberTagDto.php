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

namespace App\Interface\Admin\DTO\Member;

use App\Domain\Member\Contract\MemberTagInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 会员标签 DTO.
 */
class MemberTagDto implements MemberTagInput
{
    public ?int $id = null;

    #[Required]
    public string $name = '';

    public ?string $color = null;

    public ?string $description = null;

    public string $status = 'active';

    public ?int $sort_order = null;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
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
            'color' => $this->color,
            'description' => $this->description,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
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
