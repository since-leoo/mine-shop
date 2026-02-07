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

namespace App\Domain\Catalog\Product\Contract;

interface ProductSnapshotInterface
{
    /**
     * @param array<int, int> $skuIds
     * @return array<int, array<string, mixed>>
     */
    public function getSkuSnapshots(array $skuIds): array;

    /**
     * 获取商品及其关联信息.
     *
     * @param array<int, string> $with
     * @return null|array<string, mixed>
     */
    public function getProduct(int $productId, array $with = []): ?array;
}
