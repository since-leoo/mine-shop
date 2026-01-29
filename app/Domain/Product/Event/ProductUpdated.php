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

namespace App\Domain\Product\Event;

use App\Infrastructure\Model\Product\Product;

final class ProductUpdated
{
    /**
     * @param array<int, int> $deletedSkuIds
     */
    public function __construct(
        public readonly Product $product,
        public readonly array $deletedSkuIds = []
    ) {
    }
}
