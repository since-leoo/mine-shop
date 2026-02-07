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

namespace App\Application\Commad;

use App\Domain\Coupon\Service\DomainCouponUserService;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Coupon\CouponUser;

/**
 * 用户优惠券命令服务.
 */
final class AppCouponUserCommandService extends IService
{
    public function __construct(
        private readonly DomainCouponUserService $couponUserService,
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
        $couponUserEntity = $this->couponUserService->getEntity($id);

        return $this->couponUserService->markUsed($couponUserEntity);
    }

    public function markExpired(int $id): bool
    {
        $couponUserEntity = $this->couponUserService->getEntity($id);

        return $this->couponUserService->markExpired($couponUserEntity);
    }
}
