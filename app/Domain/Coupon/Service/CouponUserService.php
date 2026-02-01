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

namespace App\Domain\Coupon\Service;

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Domain\Coupon\Repository\CouponRepository;
use App\Domain\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Model\Coupon\CouponUser;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;

/**
 * 用户优惠券领域服务.
 */
final class CouponUserService
{
    public function __construct(
        private readonly CouponRepository $couponRepository,
        private readonly CouponUserRepository $couponUserRepository
    ) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->couponUserRepository->page($filters, $page, $pageSize);
    }

    public function findById(int $id): ?CouponUserEntity
    {
        return $this->couponUserRepository->findById($id);
    }

    /**
     * @return CouponUser[]
     */
    public function issue(int $couponId, array $memberIds, ?string $expireAt = null): array
    {
        $coupon = $this->couponRepository->findById($couponId);
        if (! $coupon) {
            throw new \InvalidArgumentException('优惠券不存在');
        }

        $now = Carbon::now();
        $coupon->assertActive()->assertEffectiveAt($now);

        $validMemberIds = Member::whereIn('id', $memberIds)->pluck('id')->all();
        if (empty($validMemberIds)) {
            throw new \InvalidArgumentException('没有可用的会员ID');
        }

        $available = $coupon->getTotalQuantity() - $this->couponRepository->countIssued($couponId);
        $coupon->assertAvailableStock($available, \count($validMemberIds));

        $finalExpire = $coupon->resolveExpireAt($expireAt, $now)->toDateTimeString();

        $created = [];
        foreach ($validMemberIds as $memberId) {
            $memberCount = $this->couponRepository->countIssuedByMember($couponId, (int) $memberId);
            if (! $coupon->canMemberReceive($memberCount)) {
                continue;
            }

            $entity = CouponUserEntity::issue($couponId, (int) $memberId, $now->toDateTimeString(), $finalExpire);
            $created[] = $this->couponUserRepository->createFromEntity($entity);
        }

        return $created;
    }

    /**
     * 优惠券使用
     */
    public function markUsed(CouponUserEntity $entity, ?int $orderId = null): bool
    {
        $entity->markUsed(Carbon::now()->toDateTimeString(), $orderId);

        $result = $this->couponUserRepository->updateFromEntity($entity);
        $this->couponRepository->syncUsageStatistics((int) $entity->getCouponId());
        return $result;
    }

    /**
     * 优惠券过期
     */
    public function markExpired(CouponUserEntity $entity): bool
    {
        $entity->markExpired();
        return $this->couponUserRepository->updateFromEntity($entity);
    }
}
