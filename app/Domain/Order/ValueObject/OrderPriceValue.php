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
    private float $goodsAmount = 0.0;

    private float $discountAmount = 0.0;

    private float $shippingFee = 0.0;

    private float $totalAmount = 0.0;

    private float $payAmount = 0.0;

    public function setGoodsAmount(float $goodsAmount): void
    {
        $this->goodsAmount = round($goodsAmount, 2);
        $this->recalculate();
    }

    public function getGoodsAmount(): float
    {
        return $this->goodsAmount;
    }

    public function setDiscountAmount(float $discountAmount): void
    {
        $this->discountAmount = round($discountAmount, 2);
        $this->recalculate();
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function setShippingFee(float $shippingFee): void
    {
        $this->shippingFee = round($shippingFee, 2);
        $this->recalculate();
    }

    public function getShippingFee(): float
    {
        return $this->shippingFee;
    }

    public function setTotalAmount(float $totalAmount): void
    {
        $this->totalAmount = round($totalAmount, 2);
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setPayAmount(float $payAmount): void
    {
        $this->payAmount = round($payAmount, 2);
    }

    public function getPayAmount(): float
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
        $this->totalAmount = round($this->goodsAmount - $this->discountAmount, 2);
        $this->payAmount = round($this->totalAmount + $this->shippingFee, 2);
    }
}
