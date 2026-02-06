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

namespace App\Domain\Product\Contract;

/**
 * 品牌输入契约接口.
 */
interface BrandInput
{
    public function getId(): int;

    public function getName(): string;

    public function getLogo(): ?string;

    public function getDescription(): ?string;

    public function getWebsite(): ?string;

    public function getSort(): int;

    public function getStatus(): string;

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     */
    public function toArray(): array;
}
