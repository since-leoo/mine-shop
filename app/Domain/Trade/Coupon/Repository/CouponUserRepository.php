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

namespace App\Domain\Trade\Coupon\Repository;

use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Error\CouponError;
use App\Infrastructure\Model\Coupon\CouponUser;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

/**
 * 用户优惠券仓储.
 *
 * @extends IRepository<CouponUser>
 */
final class CouponUserRepository extends IRepository
{
    public function __construct(protected readonly CouponUser $model) {}

    public function createFromEntity(CouponUserEntity $entity): CouponUser
    {
        $couponUser = $this->create($entity->toArray());
        if (! isset($couponUser->id)) {
            throw CouponError::USER_COUPON_CREATE_FAILED->internal();
        }
        $entity->setId((int) $couponUser->id);
        return $couponUser;
    }

    public function updateFromEntity(CouponUserEntity $entity): bool
    {
        $couponUser = $this->findById($entity->getId());
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
        return $this->getQuery()->where('coupon_id', $couponId)
            ->where('status', 'used')
            ->count();
    }

    public function countByCouponId(int $couponId): int
    {
        return $this->getQuery()->where('coupon_id', $couponId)->count();
    }

    public function countByMember(int $memberId, ?string $status = null): int
    {
        return $this->getQuery()->where('member_id', $memberId)
            ->when($status !== null, static fn ($query) => $query->where('status', $status))
            ->count();
    }

    public function countByMemberForCoupon(int $memberId, int $couponId): int
    {
        return $this->getQuery()->where('member_id', $memberId)
            ->where('coupon_id', $couponId)
            ->count();
    }

    public function listByMember(int $memberId, ?string $status = null, int $limit = 50): array
    {
        return $this->model->with('coupon')
            ->where('member_id', $memberId)
            ->when($status !== null, static fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @param array<int, int> $couponIds
     * @return array<int, int>
     */
    public function countByMemberForCoupons(int $memberId, array $couponIds): array
    {
        if ($couponIds === []) {
            return [];
        }

        return $this->model->query()
            ->selectRaw('coupon_id, COUNT(*) as total')
            ->where('member_id', $memberId)
            ->whereIn('coupon_id', $couponIds)
            ->groupBy('coupon_id')
            ->pluck('total', 'coupon_id')
            ->toArray();
    }

    /**
     * 按会员 + 优惠券 ID 列表查找未使用且未过期的 coupon_user 记录.
     *
     * @param array<int> $couponIds
     * @return array<int, CouponUser> coupon_id => CouponUser
     */
    public function findUnusedByMemberAndCouponIds(int $memberId, array $couponIds): array
    {
        if ($couponIds === []) {
            return [];
        }

        return $this->model->with('coupon')
            ->where('member_id', $memberId)
            ->whereIn('coupon_id', $couponIds)
            ->where('status', 'unused')
            ->where(static function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', Carbon::now());
            })
            ->get()
            ->keyBy('coupon_id')
            ->all();
    }

    /**
     * 使用优惠券.
     *
     * @param int $id coupon_user.id
     * @param int $orderId 订单 ID
     * @return int 优惠券 ID
     */
    public function useForOrder(int $id, int $orderId): int
    {
        $row = $this->getQuery()->where('id', $id)->lockForUpdate()->first();
        if ($row !== null && $row->status === 'used' && (int) $row->order_id === $orderId) {
            return (int) $row->coupon_id;
        }
        if ($row === null || $row->status !== 'unused' || ($row->expire_at !== null && Carbon::parse($row->expire_at)->isPast())) {
            throw CouponError::USED_OR_EXPIRED->exception();
        }
        $row->update(['status' => 'used', 'order_id' => $orderId, 'used_at' => Carbon::now()]);
        return (int) $row->coupon_id;
    }

    /**
     * 释放优惠券.
     *
     * @param int $orderId 订单 ID
     * @return array 优惠券 ID 列表
     */
    public function releaseForOrder(int $orderId): array
    {
        $rows = $this->getQuery()->where('order_id', $orderId)->where('status', 'used')->lockForUpdate()->get();
        $couponIds = [];
        foreach ($rows as $row) {
            $couponIds[] = (int) $row->coupon_id;
            $expired = $row->expire_at !== null && Carbon::parse($row->expire_at)->isPast();
            $row->update(['status' => $expired ? 'expired' : 'unused', 'order_id' => null, 'used_at' => null]);
        }
        return array_values(array_unique($couponIds));
    }
}
