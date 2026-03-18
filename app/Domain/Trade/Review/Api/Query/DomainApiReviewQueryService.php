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

namespace App\Domain\Trade\Review\Api\Query;

use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Abstract\IService;
use Hyperf\Collection\Collection;

/**
 * 小程序评价查询服务.
 */
final class DomainApiReviewQueryService extends IService
{
    public function __construct(
        private readonly ReviewRepository $repository,
    ) {}

    /**
     * 获取商品评价列表（仅返回 approved，支持筛选，按时间倒序）.
     *
     * @param array{rating_level?: string, has_images?: bool} $filters
     * @return array{total: int, list: array, page: int, page_size: int}
     */
    public function listByProduct(int $productId, array $filters, int $page, int $pageSize): array
    {
        return [
            'total' => $this->repository->countApprovedProductReviews($productId, $filters),
            'list' => $this->repository->getApprovedProductReviews($productId, $filters, $page, $pageSize),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取商品评价统计（好评/中评/差评/有图数）.
     *
     * @return array{total: int, good: int, medium: int, bad: int, with_images: int}
     */
    public function getProductStats(int $productId): array
    {
        return $this->repository->getProductStats($productId);
    }

    /**
     * 获取商品评价摘要（商品详情页用）.
     *
     * @return array{total: int, list: array}
     */
    public function getProductSummary(int $productId, int $limit = 3): array
    {
        return [
            'total' => $this->repository->countApprovedProductReviews($productId),
            'list' => $this->repository->getApprovedProductSummary($productId, $limit),
        ];
    }
}
