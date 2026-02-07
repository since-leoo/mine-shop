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

namespace App\Domain\Marketing\Coupon\Api\Query;

use App\Domain\Marketing\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 面向 API 场景的用户优惠券查询领域服务.
 */
final class DomainApiCouponUserQueryService extends IService
{
    public function __construct(public readonly CouponUserRepository $repository) {}

    /**
     * @param array<int, int> $couponIds
     * @return array<int, int>
     */
    public function countByMemberForCoupons(int $memberId, array $couponIds): array
    {
        return $this->repository->countByMemberForCoupons($memberId, $couponIds);
    }

    public function countByMemberForCoupon(int $memberId, int $couponId): int
    {
        return $this->repository->countByMemberForCoupon($memberId, $couponId);
    }

    public function listByMember(int $memberId, ?string $status = null, int $limit = 50): array
    {
        return $this->repository->listByMember($memberId, $status, $limit);
    }

    /**
     * 会员优惠券数量统计.
     */
    public function countByMember(int $memberId, ?string $status = null): int
    {
        return $this->repository->countByMember($memberId, $status);
    }
}
