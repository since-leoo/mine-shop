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
use App\Infrastructure\Abstract\IService;

/**
 * 后台评价命令服务.
 */
final class AppReviewCommandService extends IService
{
    public function __construct(
        private readonly DomainReviewService $reviewService
    ) {}

    /**
     * 审核通过评价.
     */
    public function approve(int $id): bool
    {
        $entity = $this->reviewService->getEntity($id);
        return $this->reviewService->approve($entity);
    }

    /**
     * 审核拒绝评价.
     */
    public function reject(int $id): bool
    {
        $entity = $this->reviewService->getEntity($id);
        return $this->reviewService->reject($entity);
    }

    /**
     * 管理员回复评价.
     */
    public function reply(int $id, string $content): bool
    {
        $entity = $this->reviewService->getEntity($id);
        return $this->reviewService->reply($entity, $content);
    }
}
