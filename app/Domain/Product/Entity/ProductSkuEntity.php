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

namespace App\Domain\Product\Entity;

/**
 * 商品SKU实体.
 */
final class ProductSkuEntity
{
    private ?int $id = null;

    private ?string $skuCode = null;

    private string $skuName = '';

    private mixed $specValues = null;

    private ?string $image = null;

    private float $costPrice = 0.0;

    private float $marketPrice = 0.0;

    private float $salePrice = 0.0;

    private int $stock = 0;

    private int $warningStock = 0;

    private float $weight = 0.0;

    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getSkuCode(): ?string
    {
        return $this->skuCode;
    }

    public function setSkuCode(?string $skuCode): void
    {
        $this->skuCode = $skuCode;
    }

    public function getSkuName(): string
    {
        return $this->skuName;
    }

    public function setSkuName(string $skuName): void
    {
        $this->skuName = $skuName;
    }

    public function getSpecValues(): mixed
    {
        return $this->specValues;
    }

    public function setSpecValues(mixed $specValues): void
    {
        $this->specValues = $specValues;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getCostPrice(): float
    {
        return $this->costPrice;
    }

    public function setCostPrice(float $costPrice): void
    {
        $this->costPrice = $costPrice;
    }

    public function getMarketPrice(): float
    {
        return $this->marketPrice;
    }

    public function setMarketPrice(float $marketPrice): void
    {
        $this->marketPrice = $marketPrice;
    }

    public function getSalePrice(): float
    {
        return $this->salePrice;
    }

    public function setSalePrice(float $salePrice): void
    {
        $this->salePrice = $salePrice;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getWarningStock(): int
    {
        return $this->warningStock;
    }

    public function setWarningStock(int $warningStock): void
    {
        $this->warningStock = $warningStock;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->getId(),
            'sku_code' => $this->getSkuCode(),
            'sku_name' => $this->getSkuName(),
            'spec_values' => $this->getSpecValues(),
            'image' => $this->getImage(),
            'cost_price' => $this->getCostPrice(),
            'market_price' => $this->getMarketPrice(),
            'sale_price' => $this->getSalePrice(),
            'stock' => $this->getStock(),
            'warning_stock' => $this->getWarningStock(),
            'weight' => $this->getWeight(),
            'status' => $this->getStatus(),
        ], static fn ($value) => $value !== null);
    }
}
