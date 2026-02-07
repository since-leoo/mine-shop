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

namespace App\Domain\Order\ValueObject;

final class OrderPriceValue
{
    private int $goodsAmount = 0;

    private int $discountAmount = 0;

    private int $shippingFee = 0;

    private int $totalAmount = 0;

    private int $payAmount = 0;

    public function setGoodsAmount(int $goodsAmount): void
    {
        $this->goodsAmount = $goodsAmount;
        $this->recalculate();
    }

    public function getGoodsAmount(): int
    {
        return $this->goodsAmount;
    }

    public function setDiscountAmount(int $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
        $this->recalculate();
    }

    public function getDiscountAmount(): int
    {
        return $this->discountAmount;
    }

    public function setShippingFee(int $shippingFee): void
    {
        $this->shippingFee = $shippingFee;
        $this->recalculate();
    }

    public function getShippingFee(): int
    {
        return $this->shippingFee;
    }

    public function setTotalAmount(int $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setPayAmount(int $payAmount): void
    {
        $this->payAmount = $payAmount;
    }

    public function getPayAmount(): int
    {
        return $this->payAmount;
    }

    public function toArray(): array
    {
        return [
            'goods_amount' => $this->getGoodsAmount(),
            'discount_amount' => $this->getDiscountAmount(),
            'shipping_fee' => $this->getShippingFee(),
            'total_amount' => $this->getTotalAmount(),
            'pay_amount' => $this->getPayAmount(),
        ];
    }

    private function recalculate(): void
    {
        $this->totalAmount = $this->goodsAmount - $this->discountAmount;
        $this->payAmount = $this->totalAmount + $this->shippingFee;
    }
}
