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

use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Domain\Trade\Review\Service\DomainReviewService;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Review\Review;
use Hyperf\Collection\Collection;

/**
 * 后台评价查询服务.
 */
final class AppReviewQueryService extends IService
{
    public function __construct(
        public readonly ReviewRepository $repository,
        private readonly DomainReviewService $reviewService
    ) {}

    /**
     * 分页查询评价列表.
     *
     * @param array $params 筛选参数（status, rating, product_id, member_id, start_date, end_date）
     */
    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    /**
     * 查询单条评价详情.
     *
     * @throws \RuntimeException 评价不存在时抛出异常
     */
    /**
     * 查询单条评价详情.
     *
     * @throws \RuntimeException 评价不存在时抛出异常
     */
    public function findById(mixed $id): mixed
    {
        /** @var null|Review $review */
        $review = $this->repository->findById($id);
        if (! $review) {
            throw new \RuntimeException('评价不存在');
        }
        return $review;
    }

    /**
     * 按订单ID查询评价列表.
     */
    public function listByOrderId(int $orderId): Collection
    {
        return Review::where('order_id', $orderId)->orderByDesc('id')->get();
    }

    /**
     * 获取评价统计数据.
     */
    public function stats(): array
    {
        return $this->reviewService->stats();
    }
}
