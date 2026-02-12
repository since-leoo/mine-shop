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

namespace App\Domain\Trade\Coupon\Api\Command;

use App\Infrastructure\Abstract\IService;
use Carbon\Carbon;
use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use App\Domain\Trade\Coupon\Repository\CouponRepository;
use App\Domain\Trade\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Coupon\Service\DomainCouponService;
use App\Infrastructure\Model\Coupon\CouponUser;

final class DomainApiCouponUserCommandService extends IService
{
    public function __construct(
        private readonly DomainCouponService $couponService,
        private readonly CouponRepository $couponRepository,
        private readonly CouponUserRepository $repository,
    ) {}

    /**
     * 会员领取优惠券.
     */
    public function receive(int $memberId, int $couponId): CouponUser
    {
        // 1. 获取优惠券实体并验证
        $coupon = $this->couponService->getEntity($couponId);

        $now = Carbon::now();

        // 2. 验证优惠券状态和有效期
        $coupon->assertActive()->assertEffectiveAt($now);

        // 3. 检查库存
        $available = $coupon->getTotalQuantity() - $this->couponRepository->countIssued($couponId);
        $coupon->assertAvailableStock($available, 1);

        // 4. 检查用户领取限制
        $memberCount = $this->couponRepository->countIssuedByMember($couponId, $memberId);
        if (! $coupon->canMemberReceive($memberCount)) {
            throw new \RuntimeException('已达到领取上限');
        }

        // 5. 计算过期时间
        $finalExpire = $coupon->resolveExpireAt(null, $now)->toDateTimeString();

        // 6. 创建会员优惠券实体
        $entity = CouponUserEntity::issue($couponId, $memberId, $now->toDateTimeString(), $finalExpire);

        // 7. 持久化
        return $this->repository->createFromEntity($entity);
    }
}
