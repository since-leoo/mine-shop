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
        $this->unitPrice = round($unitPrice, 2);
        $this->syncTotalPrice();
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(0, $quantity);
        $this->syncTotalPrice();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = round($totalPrice, 2);
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

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $item = new self();
        $item->setSkuId((int) ($payload['sku_id'] ?? 0));
        $item->setProductId((int) ($payload['product_id'] ?? 0));
        $item->setProductName((string) ($payload['product_name'] ?? ''));
        $item->setSkuName((string) ($payload['sku_name'] ?? ''));
        $item->setProductImage($payload['product_image'] ?? null);
        $item->setSpecValues(\is_array($payload['spec_values'] ?? null) ? $payload['spec_values'] : []);
        if (isset($payload['unit_price'])) {
            $item->setUnitPrice((float) $payload['unit_price']);
        }
        $item->setQuantity((int) ($payload['quantity'] ?? 0));
        $item->ensureQuantityPositive();
        $item->setWeight(isset($payload['weight']) ? (float) $payload['weight'] : 0.0);
        return $item;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public function attachSnapshot(array $snapshot): void
    {
        $this->setProductId((int) ($snapshot['product_id'] ?? $this->productId));
        $this->setProductName((string) ($snapshot['product_name'] ?? $this->productName));
        $this->setSkuName((string) ($snapshot['sku_name'] ?? $this->skuName));
        $image = $snapshot['sku_image'] ?? $snapshot['product_image'] ?? null;
        if ($image !== null) {
            $this->setProductImage((string) $image);
        }
        $specValues = $snapshot['spec_values'] ?? null;
        if (\is_array($specValues)) {
            $this->setSpecValues($specValues);
        }
        if (isset($snapshot['sale_price'])) {
            $this->setUnitPrice((float) $snapshot['sale_price']);
        }
        if (isset($snapshot['weight'])) {
            $this->setWeight((float) $snapshot['weight']);
        }
    }

    public function ensureQuantityPositive(): void
    {
        if ($this->quantity <= 0) {
            throw new \DomainException('订单商品数量必须大于0');
        }
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
            'total_price' => $this->getTotalPrice(),
        ];
    }

    private function syncTotalPrice(): void
    {
        if ($this->quantity <= 0) {
            $this->totalPrice = 0.0;
            return;
        }

        $this->totalPrice = (float) bcmul(
            number_format($this->unitPrice, 2, '.', ''),
            (string) $this->quantity,
            2
        );
    }
}
