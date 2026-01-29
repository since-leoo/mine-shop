<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

final class OrderDraftItemEntity
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

    private float $unitPrice = 0.0;

    private int $quantity = 0;

    private float $totalPrice = 0.0;

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

    public function setUnitPrice(float $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(0, $quantity);
        $this->recalculateTotal();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getTotalPrice(): float
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

    private function recalculateTotal(): void
    {
        $this->totalPrice = round($this->unitPrice * $this->quantity, 2);
    }
}
