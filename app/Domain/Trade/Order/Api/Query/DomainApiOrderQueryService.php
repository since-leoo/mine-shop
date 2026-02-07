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

namespace App\Domain\Trade\Order\Api\Query;

use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Order\Order;
use Hyperf\Contract\LengthAwarePaginatorInterface;

/**
 * 面向 API 场景的订单查询领域服务.
 */
final class DomainApiOrderQueryService extends IService
{
    public function __construct(public readonly OrderRepository $repository) {}

    /**
     * 会员订单分页列表.
     */
    public function paginateByMember(
        int $memberId,
        string $status = 'all',
        int $page = 1,
        int $pageSize = 10
    ): LengthAwarePaginatorInterface {
        return $this->repository->paginateByMember($memberId, $status, $page, $pageSize);
    }

    /**
     * 会员订单详情.
     */
    public function findMemberOrderDetail(int $memberId, string $orderNo): ?Order
    {
        return $this->repository->findMemberOrderDetail($memberId, $orderNo);
    }

    /**
     * 会员各状态订单数量统计.
     *
     * @return array [pending, paid, shipped, completed, afterSale]
     */
    public function countByMemberStatuses(int $memberId): array
    {
        return $this->repository->countByMemberAndStatuses($memberId);
    }
}
