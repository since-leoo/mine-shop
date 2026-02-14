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

use App\Domain\Trade\Order\Service\DomainOrderService;

final class AppOrderQueryService
{
    public function __construct(
        private readonly DomainOrderService $orderService
    ) {}

    /**
     * 后台订单分页列表.
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->orderService->page($filters, $page, $pageSize);
    }

    /**
     * 后台订单统计.
     */
    public function stats(array $filters): array
    {
        return $this->orderService->stats($filters);
    }

    /**
     * 后台订单详情.
     */
    public function detail(int $id): ?array
    {
        return $this->orderService->findDetail($id);
    }
}
