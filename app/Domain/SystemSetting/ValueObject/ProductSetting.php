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

namespace App\Domain\SystemSetting\ValueObject;

/**
 * 商品配置值对象.
 *
 * 负责提供商品领域所需的配置数据的强类型访问能力
 * 防止业务层直接依赖数组/字符串。
 */
final class ProductSetting
{
    /**
     * @param string[] $contentFilter
     */
    public function __construct(
        private readonly bool $autoGenerateSku,
        private readonly int $maxGallery,
        private readonly int $stockWarning,
        private readonly bool $allowPreorder,
        private readonly array $contentFilter,
    ) {}

    public function autoGenerateSku(): bool
    {
        return $this->autoGenerateSku;
    }

    public function maxGallery(): int
    {
        return $this->maxGallery;
    }

    public function stockWarning(): int
    {
        return $this->stockWarning;
    }

    public function allowPreorder(): bool
    {
        return $this->allowPreorder;
    }

    /**
     * @return string[]
     */
    public function contentFilter(): array
    {
        return $this->contentFilter;
    }
}
