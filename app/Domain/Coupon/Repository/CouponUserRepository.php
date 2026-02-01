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

namespace App\Domain\Coupon\Repository;

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Domain\Coupon\Trait\CouponUserMapperTrait;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Coupon\CouponUser;
use App\Infrastructure\Model\Member\Member;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * 用户优惠券仓储.
 *
 * @extends IRepository<CouponUser>
 */
final class CouponUserRepository extends IRepository
{
    use CouponUserMapperTrait;

    public function __construct(protected readonly CouponUser $model) {}

    public function createFromEntity(CouponUserEntity $entity): CouponUser
    {
        $couponUser = CouponUser::create($entity->toArray());
        $entity->setId((int) $couponUser->id);
        return $couponUser;
    }

    public function updateFromEntity(CouponUserEntity $entity): bool
    {
        $couponUser = CouponUser::find($entity->getId());
        if ($couponUser === null) {
            return false;
        }

        return $couponUser->update($entity->toArray());
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['coupon_id']), static fn (Builder $q) => $q->where('coupon_id', $params['coupon_id']))
            ->when(isset($params['member_id']), static fn (Builder $q) => $q->where('member_id', $params['member_id']))
            ->when(isset($params['status']), static fn (Builder $q) => $q->where('status', $params['status']))
            ->when(isset($params['keyword']), static function (Builder $q) use ($params) {
                $memberIds = Member::where('nickname', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('phone', 'like', '%' . $params['keyword'] . '%')
                    ->pluck('id');
                return $q->whereIn('member_id', $memberIds);
            })
            ->with(['coupon', 'member'])
            ->orderByDesc('id');
    }

    public function handleItems(Collection $items): Collection
    {
        return $items->map(static function ($item) {
            $item->coupon_name = $item->coupon?->name ?? '';
            $item->member_nickname = $item->member?->nickname ?? '';
            $item->member_phone = $item->member?->phone ?? '';
            return $item;
        });
    }

    public function countUsedByCouponId(int $couponId): int
    {
        return CouponUser::where('coupon_id', $couponId)
            ->where('status', 'used')
            ->count();
    }

    public function countByCouponId(int $couponId): int
    {
        return CouponUser::where('coupon_id', $couponId)->count();
    }

    /**
     * 根据ID查询.
     */
    public function findById(int $id): ?CouponUserEntity
    {
        $info = $this->getQuery()->whereKey($id)->first();
        if (! $info instanceof CouponUser) {
            return null;
        }

        return self::mapper($info);
    }
}
