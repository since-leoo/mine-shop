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

namespace App\Application\Api\Product;

use App\Domain\Product\Api\Query\DomainApiProductQueryService;

/**
 * 小程序/前台商品读应用服务.
 */
final class AppApiProductQueryService
{
    public function __construct(
        private readonly DomainApiProductQueryService $productQueryService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function page(array $filters, int $page = 1, int $pageSize = 20): array
    {
        return $this->productQueryService->page($filters, $page, $pageSize);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function findById(int $id): ?array
    {
        return $this->productQueryService->findById($id);
    }
}
