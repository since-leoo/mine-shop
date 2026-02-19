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

use App\Domain\Trade\Coupon\Contract\CouponInput;
use App\Domain\Trade\Coupon\Entity\CouponEntity;
use App\Infrastructure\Model\Coupon\Coupon;

/**
 * 优惠券 Mapper.
 *
 * 负责实体与模型/DTO 之间的转换。
 */
final class CouponMapper
{
    /**
     * 从 DTO 创建新实体.
     *
     * @param CouponInput $dto 优惠券输入 DTO
     * @return CouponEntity 优惠券实体
     */
    public static function fromDto(CouponInput $dto): CouponEntity
    {
        $entity = new CouponEntity();
        $entity->create($dto);
        return $entity;
    }

    /**
     * 获取空实体.
     *
     * @deprecated 使用 fromDto 代替
     */
    public static function getNewEntity(): CouponEntity
    {
        return new CouponEntity();
    }

    /**
     * 从持久化模型重建实体.
     *
     * @param Coupon $coupon 数据库模型
     * @return CouponEntity 优惠券实体
     */
    public static function fromModel(Coupon $coupon): CouponEntity
    {
        $entity = new CouponEntity();
        $entity->setId((int) $coupon->id);
        $entity->setName($coupon->name);
        $entity->setType($coupon->type);
        $entity->setValue($coupon->value !== null ? (int) $coupon->value : null);
        $entity->setMinAmount($coupon->min_amount !== null ? (int) $coupon->min_amount : null);
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
