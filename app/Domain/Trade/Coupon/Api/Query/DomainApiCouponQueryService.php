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

namespace App\Domain\Trade\Coupon\Api\Query;

use App\Infrastructure\Abstract\IService;
use Hyperf\Database\Model\Collection;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Infrastructure\Model\Coupon\Coupon;

/**
 * 面向 API 场景的优惠券查询领域服务.
 */
final class DomainApiCouponQueryService extends IService
{
    public function __construct(public readonly CouponRepository $repository) {}

    /**
     * @return Collection<int, Coupon>
     */
    public function listAvailable(array $filters = [], int $limit = 20): Collection
    {
        return $this->repository->listAvailable($filters, $limit);
    }

    public function countAvailable(array $filters = []): int
    {
        return $this->repository->countAvailable($filters);
    }

    public function findOne(int $id): ?Coupon
    {
        return $this->findById($id);
    }
}
