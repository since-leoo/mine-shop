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

namespace App\Interface\Api\Controller\V1\Review;

use App\Application\Api\Review\AppApiReviewCommandService;
use App\Domain\Trade\Review\Api\Query\DomainApiReviewQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\Review\CreateReviewRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/review')]
final class ReviewController extends AbstractController
{
    public function __construct(
        private readonly AppApiReviewCommandService $commandService,
        private readonly DomainApiReviewQueryService $queryService,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request,
    ) {}

    /**
     * 提交评价（需认证）.
     */
    #[PostMapping(path: '')]
    #[Middleware(TokenMiddleware::class)]
    public function store(CreateReviewRequest $request): Result
    {
        $review = $this->commandService->create(
            $this->currentMember->id(),
            $request->toDto()
        );

        return $this->success(['id' => $review->id], '评价提交成功');
    }

    /**
     * 商品评价列表.
     */
    #[GetMapping(path: 'product/{id}')]
    public function productReviews(int $id): Result
    {
        $page = (int) $this->request->query('page', 1);
        $pageSize = (int) $this->request->query('page_size', 10);
        $filters = [];

        if ($this->request->has('rating_level')) {
            $filters['rating_level'] = $this->request->query('rating_level');
        }
        if ($this->request->has('has_images')) {
            $filters['has_images'] = (bool) $this->request->query('has_images');
        }

        $result = $this->queryService->listByProduct($id, $filters, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 商品评价统计.
     */
    #[GetMapping(path: 'product/{id}/stats')]
    public function productStats(int $id): Result
    {
        $stats = $this->queryService->getProductStats($id);

        return $this->success($stats);
    }

    /**
     * 商品评价摘要（详情页用）.
     */
    #[GetMapping(path: 'product/{id}/summary')]
    public function productSummary(int $id): Result
    {
        $limit = (int) $this->request->query('limit', 3);
        $summary = $this->queryService->getProductSummary($id, $limit);

        return $this->success($summary);
    }
}
