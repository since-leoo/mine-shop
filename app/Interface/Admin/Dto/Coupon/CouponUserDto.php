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

namespace App\Interface\Admin\Dto\Coupon;

use App\Domain\Coupon\Contract\CouponUserInput;

/**
 * 用户优惠券DTO.
 */
final class CouponUserDto implements CouponUserInput
{
    public ?int $id = null;

    public ?int $couponId = null;

    public ?int $memberId = null;

    public ?int $orderId = null;

    public ?string $status = null;

    public ?string $receivedAt = null;

    public ?string $usedAt = null;

    public ?string $expireAt = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getCouponId(): ?int
    {
        return $this->couponId;
    }

    public function getMemberId(): ?int
    {
        return $this->memberId;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getReceivedAt(): ?string
    {
        return $this->receivedAt;
    }

    public function getUsedAt(): ?string
    {
        return $this->usedAt;
    }

    public function getExpireAt(): ?string
    {
        return $this->expireAt;
    }
}
