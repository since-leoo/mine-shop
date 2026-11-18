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

namespace Plugin\SupplyChain\Interfaces\Data;

use App\Infrastructure\Error\SupplyChainError;

final readonly class ProductQuery
{
    public function __construct(
        public int $page = 1,
        public int $pageSize = 20,
        public string $keyword = '',
        public ?string $supplierId = null,
        public ?string $categoryId = null,
        public ?int $source = null,
    ) {
        if ($page < 1 || $pageSize < 1 || $pageSize > 100) {
            throw SupplyChainError::PRODUCT_PAGINATION_INVALID->invalid();
        }
    }
}
