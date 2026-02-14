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

namespace App\Application\Admin\Review;

use App\Domain\Trade\Review\Service\DomainReviewService;
use Hyperf\Collection\Collection;

/**
 * 后台评价查询服务.
 */
final class AppReviewQueryService
{
    public function __construct(
        private readonly DomainReviewService $reviewService,
    ) {}

    /**
     * 分页查询评价列表.
     *
     * @param array $params 筛选参数（status, rating, product_id, member_id, start_date, end_date）
     */
    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        return $this->reviewService->page($params, $page, $pageSize);
    }

    /**
     * 查询单条评价详情.
     *
     * @throws \RuntimeException 评价不存在时抛出异常
     */
    public function findById(mixed $id): mixed
    {
        return $this->reviewService->getEntity((int) $id);
    }

    /**
     * 按订单ID查询评价列表.
     */
    public function listByOrderId(int $orderId): Collection
    {
        return $this->reviewService->listByOrderId($orderId);
    }

    /**
     * 获取评价统计数据.
     */
    public function stats(): array
    {
        return $this->reviewService->stats();
    }
}
