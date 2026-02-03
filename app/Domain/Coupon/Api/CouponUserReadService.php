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

namespace App\Domain\Coupon\Api;

use App\Domain\Coupon\Repository\CouponUserRepository;

/**
 * 提供用户优惠券读取能力.
 */
final class CouponUserReadService
{
    public function __construct(private readonly CouponUserRepository $couponUserRepository) {}

    /**
     * @param array<int, int> $couponIds
     * @return array<int, int>
     */
    public function countByMemberForCoupons(int $memberId, array $couponIds): array
    {
        return $this->couponUserRepository->countByMemberForCoupons($memberId, $couponIds);
    }

    public function countByMemberForCoupon(int $memberId, int $couponId): int
    {
        return $this->couponUserRepository->countByMemberForCoupon($memberId, $couponId);
    }

    public function listByMember(int $memberId, ?string $status = null, int $limit = 50): array
    {
        return $this->couponUserRepository->listByMember($memberId, $status, $limit);
    }
}
