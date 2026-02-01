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

namespace App\Application\Coupon\Assembler;

use App\Domain\Coupon\Entity\CouponEntity;

/**
 * 优惠券组装器.
 */
final class CouponAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): CouponEntity
    {
        $couponEntity = new CouponEntity();

        $couponEntity->setName($payload['name']);
        $couponEntity->setType($payload['type']);
        $couponEntity->setValue(isset($payload['value']) ? (float) $payload['value'] : null);
        $couponEntity->setMinAmount(isset($payload['min_amount']) ? (float) $payload['min_amount'] : 0.0);
        $couponEntity->setTotalQuantity(isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : null);
        $couponEntity->setPerUserLimit(isset($payload['per_user_limit']) ? (int) $payload['per_user_limit'] : 1);
        $couponEntity->defineTimeWindow($payload['start_time'] ?? null, $payload['end_time'] ?? null);
        $couponEntity->setStatus($payload['status'] ?? 'active');
        $couponEntity->setDescription($payload['description'] ?? null);

        return $couponEntity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): CouponEntity
    {
        $couponEntity = new CouponEntity();
        $couponEntity->setId($id);
        $couponEntity->setName($payload['name']);
        $couponEntity->setType($payload['type']);
        $couponEntity->setValue(isset($payload['value']) ? (float) $payload['value'] : null);
        $couponEntity->setMinAmount(isset($payload['min_amount']) ? (float) $payload['min_amount'] : 0.0);
        $couponEntity->setTotalQuantity(isset($payload['total_quantity']) ? (int) $payload['total_quantity'] : null);
        $couponEntity->setPerUserLimit(isset($payload['per_user_limit']) ? (int) $payload['per_user_limit'] : 1);
        $couponEntity->defineTimeWindow($payload['start_time'] ?? null, $payload['end_time'] ?? null);
        $couponEntity->setStatus($payload['status'] ?? 'active');
        $couponEntity->setDescription($payload['description'] ?? null);

        return $couponEntity;
    }

    public static function toUpStatusEntity(int $id): CouponEntity
    {
        $couponEntity = new CouponEntity();
        $couponEntity->setId($id);

        return $couponEntity;
    }
}
