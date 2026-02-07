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

use App\Domain\Coupon\Contract\CouponInput;

/**
 * 优惠券DTO.
 */
final class CouponDto implements CouponInput
{
    public ?int $id = null;

    public ?string $name = null;

    public ?string $type = null;

    public ?int $value = null;

    public ?int $minAmount = null;

    public ?int $totalQuantity = null;

    public ?int $perUserLimit = null;

    public ?string $startTime = null;

    public ?string $endTime = null;

    public ?string $status = null;

    public ?string $description = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function getMinAmount(): ?int
    {
        return $this->minAmount;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function getPerUserLimit(): ?int
    {
        return $this->perUserLimit;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
