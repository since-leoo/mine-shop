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

namespace App\Domain\Trade\Seckill\Entity;

use App\Domain\Trade\Seckill\Contract\SeckillProductInput;
use App\Domain\Trade\Seckill\ValueObject\ProductPrice;
use App\Domain\Trade\Seckill\ValueObject\ProductStock;
use Carbon\Carbon;

final class SeckillProductEntity
{
    private int $id = 0;

    private int $activityId;

    private int $sessionId;

    private int $productId;

    private int $productSkuId;

    private ProductPrice $price;

    private ProductStock $stock;

    private int $maxQuantityPerUser;

    private int $sortOrder;

    private bool $isEnabled;

    private ?Carbon $createdAt = null;

    private ?Carbon $updatedAt = null;

    public function __construct() {}

    public static function reconstitute(
        int $id,
        int $activityId,
        int $sessionId,
        int $productId,
        int $productSkuId,
        int $originalPrice,
        int $seckillPrice,
        int $quantity,
        int $soldQuantity,
        int $maxQuantityPerUser,
        int $sortOrder,
        bool $isEnabled,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ): self {
        $entity = new self();
        $entity->id = $id;
        $entity->activityId = $activityId;
        $entity->sessionId = $sessionId;
        $entity->productId = $productId;
        $entity->productSkuId = $productSkuId;
        $entity->price = new ProductPrice($originalPrice, $seckillPrice);
        $entity->stock = new ProductStock($quantity, $soldQuantity);
        $entity->maxQuantityPerUser = $maxQuantityPerUser;
        $entity->sortOrder = $sortOrder;
        $entity->isEnabled = $isEnabled;
        $entity->createdAt = $createdAt;
        $entity->updatedAt = $updatedAt;
        return $entity;
    }

    public function create(SeckillProductInput $dto): self
    {
        $this->activityId = $dto->getActivityId();
        $this->sessionId = $dto->getSessionId();
        $this->productId = $dto->getProductId();
        $this->productSkuId = $dto->getProductSkuId();
        $this->price = new ProductPrice($dto->getOriginalPrice(), $dto->getSeckillPrice());
        $this->stock = new ProductStock($dto->getQuantity() ?? 0, 0);
        $this->maxQuantityPerUser = $dto->getMaxQuantityPerUser() ?? 1;
        $this->sortOrder = $dto->getSortOrder() ?? 0;
        $this->isEnabled = true;
        return $this;
    }

    public function update(SeckillProductInput $dto): self
    {
        if ($dto->getOriginalPrice() !== null && $dto->getSeckillPrice() !== null) {
            $this->price = new ProductPrice($dto->getOriginalPrice(), $dto->getSeckillPrice());
        }
        if ($dto->getMaxQuantityPerUser() !== null) {
            $this->maxQuantityPerUser = $dto->getMaxQuantityPerUser();
        }
        if ($dto->getSortOrder() !== null) {
            $this->sortOrder = $dto->getSortOrder();
        }
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductSkuId(): int
    {
        return $this->productSkuId;
    }

    public function getPrice(): ProductPrice
    {
        return $this->price;
    }

    public function getStock(): ProductStock
    {
        return $this->stock;
    }

    public function getMaxQuantityPerUser(): int
    {
        return $this->maxQuantityPerUser;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function canSell(int $quantity): bool
    {
        return $this->isEnabled && ! $this->stock->isSoldOut() && $this->stock->canSell($quantity);
    }

    public function canUserPurchase(int $quantity, int $userPurchasedQuantity): bool
    {
        return ($userPurchasedQuantity + $quantity) <= $this->maxQuantityPerUser;
    }

    public function enable(): self
    {
        $this->isEnabled = true;
        return $this;
    }

    public function disable(): self
    {
        $this->isEnabled = false;
        return $this;
    }

    public function sell(int $quantity): self
    {
        $this->stock = $this->stock->sell($quantity);
        if ($this->stock->isSoldOut()) {
            $this->isEnabled = false;
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'activity_id' => $this->activityId, 'session_id' => $this->sessionId,
            'product_id' => $this->productId, 'product_sku_id' => $this->productSkuId,
            'original_price' => $this->price->getOriginalPrice(), 'seckill_price' => $this->price->getSeckillPrice(),
            'quantity' => $this->stock->getQuantity(), 'sold_quantity' => $this->stock->getSoldQuantity(),
            'max_quantity_per_user' => $this->maxQuantityPerUser, 'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
