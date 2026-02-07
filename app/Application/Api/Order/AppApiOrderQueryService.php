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

namespace App\Application\Api\Order;

use App\Domain\Order\Api\Query\DomainApiOrderQueryService;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Contract\LengthAwarePaginatorInterface;

final class AppApiOrderQueryService
{
    public function __construct(
        private readonly DomainApiOrderQueryService $orderQueryService
    ) {}

    /**
     * 获取会员订单列表（分页）.
     */
    public function getMemberOrderList(
        int $memberId,
        string $status = 'all',
        int $page = 1,
        int $pageSize = 10
    ): LengthAwarePaginatorInterface {
        return $this->orderQueryService->paginateByMember($memberId, $status, $page, $pageSize);
    }

    /**
     * 获取订单详情.
     */
    public function getOrderDetail(int $memberId, string $orderNo): ?Order
    {
        return $this->orderQueryService->findMemberOrderDetail($memberId, $orderNo);
    }

    /**
     * 获取订单统计.
     */
    public function getOrderStatistics(int $memberId): array
    {
        [$pending, $paid, $shipped, $completed, $afterSale] = $this->orderQueryService->countByMemberStatuses($memberId);

        return [
            'pending_count' => $pending,
            'paid_count' => $paid,
            'shipped_count' => $shipped,
            'completed_count' => $completed,
            'after_sale_count' => $afterSale,
        ];
    }
}
