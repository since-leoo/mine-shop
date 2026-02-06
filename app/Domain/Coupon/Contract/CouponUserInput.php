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

namespace App\Domain\Coupon\Contract;

/**
 * 用户优惠券输入契约.
 */
interface CouponUserInput
{
    public function getId(): int;

    public function getCouponId(): ?int;

    public function getMemberId(): ?int;

    public function getOrderId(): ?int;

    public function getStatus(): ?string;

    public function getReceivedAt(): ?string;

    public function getUsedAt(): ?string;

    public function getExpireAt(): ?string;
}
