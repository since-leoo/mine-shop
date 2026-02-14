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

namespace App\Domain\Trade\Review\Service;

use App\Domain\Trade\Review\Entity\ReviewEntity;
use App\Domain\Trade\Review\Mapper\ReviewMapper;
use App\Domain\Trade\Review\Repository\ReviewRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Review\Review;
use Hyperf\Collection\Collection;

final class DomainReviewService extends IService
{
    public function __construct(
        public readonly ReviewRepository $repository
    ) {}

    /**
     * 根据 ID 获取评价实体.
     */
    public function getEntity(int $id): ReviewEntity
    {
        /** @var null|Review $model */
        $model = $this->repository->findById($id);
        if (! $model) {
            throw new \RuntimeException('评价不存在');
        }
        return ReviewMapper::fromModel($model);
    }

    /**
     * 审核通过评价.
     */
    public function approve(ReviewEntity $entity): bool
    {
        $entity->approve();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 审核拒绝评价.
     */
    public function reject(ReviewEntity $entity): bool
    {
        $entity->reject();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 管理员回复评价.
     */
    public function reply(ReviewEntity $entity, string $content): bool
    {
        $entity->reply($content);
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 按订单ID查询评价列表.
     */
    public function listByOrderId(int $orderId): Collection
    {
        return Review::where('order_id', $orderId)->orderByDesc('id')->get();
    }

    /**
     * 获取评价统计数据（仪表盘用）.
     */
    public function stats(): array
    {
        return $this->repository->getStatistics();
    }
}
