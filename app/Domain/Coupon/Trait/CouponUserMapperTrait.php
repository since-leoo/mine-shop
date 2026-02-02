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

namespace App\Domain\Coupon\Trait;

use App\Domain\Coupon\Entity\CouponUserEntity;
use App\Infrastructure\Model\Coupon\CouponUser;

trait CouponUserMapperTrait
{
    public static function mapper(CouponUser $coupon): CouponUserEntity
    {
        $entity = new CouponUserEntity();
        $entity->setId((int) $coupon->id);
        $entity->setStatus($coupon->status);
        $entity->setCouponId($coupon->coupon_id !== null ? (int) $coupon->coupon_id : null);
        $entity->setMemberId($coupon->member_id !== null ? (int) $coupon->member_id : null);
        $entity->setExpireAt($coupon->expire_at ? $coupon->expire_at->toDateTimeString() : null);
        $entity->setReceivedAt($coupon->received_at ? $coupon->received_at->toDateTimeString() : null);

        /** @var null|\Carbon\Carbon $usedAt */
        $usedAt = $coupon->used_at;
        $entity->setUsedAt($usedAt?->toDateTimeString());
        $entity->setOrderId($coupon->order_id !== null ? (int) $coupon->order_id : null);
        return $entity;
    }
}
