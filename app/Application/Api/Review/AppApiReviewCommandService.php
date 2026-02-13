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

namespace App\Application\Api\Review;

use App\Domain\Trade\Review\Api\Command\DomainApiReviewCommandService;
use App\Domain\Trade\Review\Contract\ReviewInput;
use App\Infrastructure\Model\Review\Review;
use Hyperf\DbConnection\Db;

final class AppApiReviewCommandService
{
    public function __construct(
        private readonly DomainApiReviewCommandService $reviewCommandService,
    ) {}

    /**
     * 提交评价（事务管理）.
     */
    public function create(int $memberId, ReviewInput $dto): Review
    {
        return Db::transaction(fn () => $this->reviewCommandService->create($memberId, $dto));
    }
}
