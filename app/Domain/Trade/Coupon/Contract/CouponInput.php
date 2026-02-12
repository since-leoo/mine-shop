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

namespace App\Domain\Trade\Coupon\Contract;

/**
 * 优惠券输入契约.
 */
interface CouponInput
{
    public function getId(): int;

    public function getName(): ?string;

    public function getType(): ?string;

    public function getValue(): ?int;

    public function getMinAmount(): ?int;

    public function getTotalQuantity(): ?int;

    public function getPerUserLimit(): ?int;

    public function getStartTime(): ?string;

    public function getEndTime(): ?string;

    public function getStatus(): ?string;

    public function getDescription(): ?string;
}
