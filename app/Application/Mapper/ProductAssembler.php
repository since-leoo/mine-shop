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

namespace App\Application\Mapper;

use App\Domain\Product\Entity\ProductEntity;
use App\Domain\Product\Mapper\ProductMapper;

/**
 * 商品组装器：负责将请求数据转换为领域实体.
 */
final class ProductAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): ProductEntity
    {
        return ProductMapper::fromArrayForCreate($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): ProductEntity
    {
        return ProductMapper::fromArrayForUpdate($id, $payload);
    }
}
