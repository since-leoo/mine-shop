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

use App\Infrastructure\Model\Product\ProductSku;

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

    public function isActive(): bool
    {
        return $this->status === ProductSku::STATUS_ACTIVE;
    }

    public function isLowStock(): bool
    {
        if ($this->warningStock <= 0) {
            return false;
        }

        return $this->stock <= $this->warningStock;
    }

    public function markActive(): self
    {
        $this->status = ProductSku::STATUS_ACTIVE;
        return $this;
    }

    public function markInactive(): self
    {
        $this->status = ProductSku::STATUS_INACTIVE;
        return $this;
    }

    public function increaseStock(int $quantity): self
    {
        return $this->adjustStock($quantity);
    }

    public function decreaseStock(int $quantity): self
    {
        return $this->adjustStock(-$quantity);
    }

    public function ensureStockAvailable(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new BusinessException(ResultCode::FAIL, '扣减库存数量必须大于0');
        }
        if ($this->stock < $quantity) {
            throw new BusinessException(ResultCode::FAIL, 'SKU库存不足');
        }
    }

    public function assertIntegrity(): void
    {
        // Entity 层只验证业务规则，不验证格式
        // 格式验证（名称非空、价格大于0等）应该在 Request 层完成
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

    private function adjustStock(int $delta): self
    {
        if ($delta === 0) {
            return $this;
        }

        $newStock = $this->stock + $delta;
        if ($newStock < 0) {
            throw new BusinessException(ResultCode::FAIL, 'SKU库存不允许为负数');
        }

        $this->stock = $newStock;
        return $this;
    }
}
