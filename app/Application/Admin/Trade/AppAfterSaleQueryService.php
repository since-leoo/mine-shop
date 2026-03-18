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

namespace App\Application\Admin\Trade;

use App\Domain\Trade\AfterSale\Service\DomainAfterSaleQueryService;

final class AppAfterSaleQueryService
{
    public function __construct(
        private readonly DomainAfterSaleQueryService $afterSaleQueryService,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->afterSaleQueryService->pageForAdmin($filters, $page, $pageSize);
    }

    /**
     * @return array{after_sale: object, refund_record: object|null}|null
     */
    public function detail(int $id): ?array
    {
        return $this->afterSaleQueryService->detailForAdmin($id);
    }
}
