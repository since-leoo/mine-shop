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

namespace App\Interface\Admin\DTO\Product;

use App\Domain\Product\Contract\CategoryInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 分类 DTO.
 */
class CategoryDto implements CategoryInput
{
    public ?int $id = null;

    public int $parent_id = 0;

    #[Required]
    public string $name = '';

    public ?string $icon = null;

    public ?string $thumbnail = null;

    public ?string $description = null;

    public int $sort = 0;

    public string $status = 'active';

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     */
    public function toArray(): array
    {
        $data = [
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'icon' => $this->icon,
            'thumbnail' => $this->thumbnail,
            'description' => $this->description,
            'sort' => $this->sort,
            'status' => $this->status,
        ];

        return array_filter($data, static fn ($value) => $value !== null);
    }
}
