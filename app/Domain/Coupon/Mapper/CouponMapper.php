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

namespace App\Domain\Coupon\Mapper;

use App\Domain\Coupon\Entity\CouponEntity;
use App\Infrastructure\Model\Coupon\Coupon;

final class CouponMapper
{
    public static function fromModel(Coupon $coupon): CouponEntity
    {
        $entity = new CouponEntity();
        $entity->setId((int) $coupon->id);
        $entity->setName($coupon->name);
        $entity->setType($coupon->type);
        $entity->setValue($coupon->value !== null ? (float) $coupon->value : null);
        $entity->setMinAmount($coupon->min_amount !== null ? (float) $coupon->min_amount : null);
        $entity->setTotalQuantity($coupon->total_quantity !== null ? (int) $coupon->total_quantity : null);
        $entity->setUsedQuantity($coupon->used_quantity !== null ? (int) $coupon->used_quantity : null);
        $entity->setPerUserLimit($coupon->per_user_limit !== null ? (int) $coupon->per_user_limit : null);

        $startTime = $coupon->start_time ? $coupon->start_time->toDateTimeString() : null;
        $endTime = $coupon->end_time ? $coupon->end_time->toDateTimeString() : null;
        $entity->defineTimeWindow($startTime, $endTime);

        $entity->setStatus($coupon->status);
        $entity->setDescription($coupon->description);

        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(CouponEntity $entity): array
    {
        return $entity->toArray();
    }
}
