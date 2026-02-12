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

namespace App\Domain\Trade\Coupon\Mapper;

use App\Domain\Trade\Coupon\Entity\CouponUserEntity;
use App\Infrastructure\Model\Coupon\CouponUser;

final class CouponUserMapper
{
    public static function getNewEntity(): CouponUserEntity
    {
        return new CouponUserEntity();
    }

    public static function fromModel(CouponUser $couponUser): CouponUserEntity
    {
        $entity = new CouponUserEntity();
        $entity->setId((int) $couponUser->id);
        $entity->setStatus($couponUser->status);
        $entity->setCouponId($couponUser->coupon_id !== null ? (int) $couponUser->coupon_id : null);
        $entity->setMemberId($couponUser->member_id !== null ? (int) $couponUser->member_id : null);
        $entity->setExpireAt($couponUser->expire_at ? $couponUser->expire_at->toDateTimeString() : null);
        $entity->setReceivedAt($couponUser->received_at ? $couponUser->received_at->toDateTimeString() : null);
        $entity->setUsedAt($couponUser->used_at ? $couponUser->used_at->toDateTimeString() : null);
        $entity->setOrderId($couponUser->order_id !== null ? (int) $couponUser->order_id : null);
        return $entity;
    }
}
