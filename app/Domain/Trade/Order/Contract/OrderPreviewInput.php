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
 * 订单预览输入契约接口.
 */
interface OrderPreviewInput
{
    public function getMemberId(): int;

    public function getOrderType(): string;

    /**
     * @return array<int, array{sku_id: int, quantity: int}>
     */
    public function getGoodsRequestList(): array;

    public function getAddressId(): ?int;

    /**
     * @return ?array<string, mixed>
     */
    public function getUserAddress(): ?array;

    /**
     * 优惠券 ID（一次只能用一张，不传则不使用优惠券）.
     */
    public function getCouponId(): ?int;

    public function getBuyerRemark(): string;

    /**
     * 秒杀活动 ID（order_type=seckill 时必传）.
     */
    public function getActivityId(): ?int;

    /**
     * 秒杀场次 ID（order_type=seckill 时必传）.
     */
    public function getSessionId(): ?int;

    /**
     * 拼团活动 ID（order_type=group_buy 时必传）.
     */
    public function getGroupBuyId(): ?int;

    /**
     * 团号（参团时必传，开团时为空）.
     */
    public function getGroupNo(): ?string;
}
