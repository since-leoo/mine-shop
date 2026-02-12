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

namespace App\Domain\Trade\Coupon\Service;

use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;

/**
 * 优惠券服务适配器：实现主应用定义的接口.
 */
final class CouponServiceAdapter implements CouponServiceInterface
{
    public function __construct(
        private readonly CouponUserRepository $couponUserRepository,
        private readonly DomainCouponUserService $couponUserService,
    ) {}

    public function findUsableCoupon(int $memberId, int $couponId): ?array
    {
        $map = $this->couponUserRepository->findUnusedByMemberAndCouponIds($memberId, [$couponId]);
        $couponUser = $map[$couponId] ?? null;
        if (! $couponUser) {
            return null;
        }

        $coupon = $couponUser->coupon;

        return [
            'id' => (int) $couponUser->id,
            'coupon_id' => (int) $couponUser->coupon_id,
            'type' => (string) ($coupon->type ?? 'fixed'),
            'value' => (int) ($coupon->value ?? 0),
            'min_amount' => (int) ($coupon->min_amount ?? 0),
            'name' => (string) ($coupon->name ?? ''),
            'status' => (string) ($coupon->status ?? ''),
        ];
    }

    public function settleCoupon(int $couponUserId, int $orderId): void
    {
        $entity = $this->couponUserService->getEntity($couponUserId);
        $this->couponUserService->markUsed($entity, $orderId);
    }
}
