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

namespace App\Domain\Order\Entity;

final class OrderItemEntity
{
    private int $productId = 0;

    private int $skuId = 0;

    private string $productName = '';

    private string $skuName = '';

    private ?string $productImage = null;

    /**
     * @var array<string, mixed>
     */
    private array $specValues = [];

    private int $unitPrice = 0;

    private int $quantity = 0;

    private int $totalPrice = 0;

    private float $weight = 0.0;

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setSkuId(int $skuId): void
    {
        $this->skuId = $skuId;
    }

    public function getSkuId(): int
    {
        return $this->skuId;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setSkuName(string $skuName): void
    {
        $this->skuName = $skuName;
    }

    public function getSkuName(): string
    {
        return $this->skuName;
    }

    public function setProductImage(?string $productImage): void
    {
        $this->productImage = $productImage;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    /**
     * @param array<string, mixed> $specValues
     */
    public function setSpecValues(array $specValues): void
    {
        $this->specValues = $specValues;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSpecValues(): array
    {
        return $this->specValues;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(0, $quantity);
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setTotalPrice(int $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function toArray(): array
    {
        return [
            'sku_id' => $this->getSkuId(),
            'product_name' => $this->getProductName(),
            'sku_name' => $this->getSkuName(),
            'product_image' => $this->getProductImage(),
            'spec_values' => $this->getSpecValues(),
            'unit_price' => $this->getUnitPrice(),
            'quantity' => $this->getQuantity(),
            'total_price' => (int) bcmul((string) $this->getUnitPrice(), (string) $this->getQuantity(), 2),
        ];
    }
}
