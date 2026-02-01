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

namespace App\Application\Coupon\Service;

use App\Domain\Coupon\Service\CouponUserService;
use App\Infrastructure\Model\Coupon\CouponUser;

/**
 * 用户优惠券命令服务.
 */
final class CouponUserCommandService
{
    public function __construct(
        private readonly CouponUserService $couponUserService,
        private readonly CouponUserQueryService $queryService
    ) {}

    /**
     * @param int[] $memberIds
     * @return CouponUser[]
     */
    public function issue(int $couponId, array $memberIds, ?string $expireAt = null): array
    {
        return $this->couponUserService->issue($couponId, $memberIds, $expireAt);
    }

    public function markUsed(int $id): bool
    {
        $couponUserEntity = $this->queryService->find($id);

        return $this->couponUserService->markUsed($couponUserEntity);
    }

    public function markExpired(int $id): bool
    {
        $couponUserEntity = $this->queryService->find($id);

        return $this->couponUserService->markExpired($couponUserEntity);
    }
}
