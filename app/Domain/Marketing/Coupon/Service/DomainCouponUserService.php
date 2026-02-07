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

namespace App\Domain\Marketing\Coupon\Service;

use App\Domain\Marketing\Coupon\Entity\CouponUserEntity;
use App\Domain\Marketing\Coupon\Mapper\CouponUserMapper;
use App\Domain\Marketing\Coupon\Repository\CouponRepository;
use App\Domain\Marketing\Coupon\Repository\CouponUserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Coupon\CouponUser;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;

/**
 * 用户优惠券领域服务.
 */
final class DomainCouponUserService extends IService
{
    public function __construct(
        private readonly CouponRepository $couponRepository,
        public readonly CouponUserRepository $repository,
        private readonly DomainCouponService $couponService,
    ) {}

    public function getEntity(int $id): CouponUserEntity
    {
        /** @var null|CouponUser $couponUser */
        $couponUser = $this->findById($id);
        if (! $couponUser) {
            throw new BusinessException(ResultCode::NOT_FOUND, '用户优惠券不存在');
        }

        return CouponUserMapper::fromModel($couponUser);
    }

    /**
     * @return CouponUser[]
     */
    public function issue(int $couponId, array $memberIds, ?string $expireAt = null): array
    {
        $coupon = $this->couponService->getEntity($couponId);

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
            $created[] = $this->repository->createFromEntity($entity);
        }

        return $created;
    }

    /**
     * 优惠券使用.
     */
    public function markUsed(CouponUserEntity $entity, ?int $orderId = null): bool
    {
        $entity->markUsed(Carbon::now()->toDateTimeString(), $orderId);

        $result = $this->repository->updateFromEntity($entity);
        $this->couponRepository->syncUsageStatistics((int) $entity->getCouponId());
        return $result;
    }

    /**
     * 优惠券过期
     */
    public function markExpired(CouponUserEntity $entity): bool
    {
        $entity->markExpired();
        return $this->repository->updateFromEntity($entity);
    }

    /**
     * 获取会员优惠券数量.
     */
    public function countByMember(int $memberId, ?string $status = null): int
    {
        return $this->repository->countByMember($memberId, $status);
    }
}
