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

namespace App\Domain\Trade\Order\Contract;

/**
 * 优惠券服务接口（由优惠券插件实现）.
 *
 * 主应用只依赖此接口，不直接依赖插件类。
 */
interface CouponServiceInterface
{
    /**
     * 查找会员可用的优惠券记录.
     *
     * @return null|array{id: int, coupon_id: int, type: string, value: int, min_amount: int, name: string, status: string}
     */
    public function findUsableCoupon(int $memberId, int $couponId): ?array;

    /**
     * 核销优惠券.
     */
    public function settleCoupon(int $couponUserId, int $orderId): void;
}
