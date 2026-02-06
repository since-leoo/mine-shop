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

use App\Domain\Product\Contract\BrandInput;
use Hyperf\DTO\Annotation\Validation\Required;

/**
 * 品牌 DTO.
 */
class BrandDto implements BrandInput
{
    public ?int $id = null;

    #[Required]
    public string $name = '';

    public ?string $logo = null;

    public ?string $description = null;

    public ?string $website = null;

    public int $sort = 0;

    public string $status = 'active';

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
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
            'name' => $this->name,
            'logo' => $this->logo,
            'description' => $this->description,
            'website' => $this->website,
            'sort' => $this->sort,
            'status' => $this->status,
        ];

        return array_filter($data, static fn ($value) => $value !== null);
    }
}
