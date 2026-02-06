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

namespace App\Domain\Seckill\Entity;

use App\Domain\Seckill\Contract\SeckillProductInput;
use App\Domain\Seckill\ValueObject\ProductPrice;
use App\Domain\Seckill\ValueObject\ProductStock;
use Carbon\Carbon;

/**
 * 秒杀商品实体.
 */
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

    /**
     * 从持久化数据重建.
     */
    public static function reconstitute(
        int $id,
        int $activityId,
        int $sessionId,
        int $productId,
        int $productSkuId,
        float $originalPrice,
        float $seckillPrice,
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

    /**
     * 创建行为方法：接收DTO，内部组装设置值.
     */
    public function create(SeckillProductInput $dto): self
    {
        // Request层已验证所有必填字段，这里直接使用
        $this->activityId = $dto->getActivityId();
        $this->sessionId = $dto->getSessionId();
        $this->productId = $dto->getProductId();
        $this->productSkuId = $dto->getProductSkuId();

        // Request层已验证价格格式，这里创建值对象会进行业务规则验证
        $this->price = new ProductPrice($dto->getOriginalPrice(), $dto->getSeckillPrice());
        $this->stock = new ProductStock($dto->getQuantity() ?? 0, 0);
        $this->maxQuantityPerUser = $dto->getMaxQuantityPerUser() ?? 1;
        $this->sortOrder = $dto->getSortOrder() ?? 0;
        $this->isEnabled = true;

        return $this;
    }

    /**
     * 更新行为方法：接收DTO，内部组装设置值.
     */
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

    /**
     * 业务规则：检查商品是否可以购买.
     */
    public function canSell(int $quantity): bool
    {
        if (! $this->isEnabled) {
            return false;
        }

        if ($this->stock->isSoldOut()) {
            return false;
        }

        return $this->stock->canSell($quantity);
    }

    /**
     * 业务规则：检查用户购买数量是否合法.
     */
    public function canUserPurchase(int $quantity, int $userPurchasedQuantity): bool
    {
        if ($userPurchasedQuantity + $quantity > $this->maxQuantityPerUser) {
            return false;
        }

        return true;
    }

    /**
     * 启用商品.
     */
    public function enable(): self
    {
        $this->isEnabled = true;
        return $this;
    }

    /**
     * 禁用商品.
     */
    public function disable(): self
    {
        $this->isEnabled = false;
        return $this;
    }

    /**
     * 扣减库存.
     */
    public function sell(int $quantity): self
    {
        $this->stock = $this->stock->sell($quantity);

        // 如果售罄，自动禁用
        if ($this->stock->isSoldOut()) {
            $this->isEnabled = false;
        }

        return $this;
    }

    /**
     * 转换为数组（用于持久化）.
     */
    public function toArray(): array
    {
        return [
            'activity_id' => $this->activityId,
            'session_id' => $this->sessionId,
            'product_id' => $this->productId,
            'product_sku_id' => $this->productSkuId,
            'original_price' => $this->price->getOriginalPrice(),
            'seckill_price' => $this->price->getSeckillPrice(),
            'quantity' => $this->stock->getQuantity(),
            'sold_quantity' => $this->stock->getSoldQuantity(),
            'max_quantity_per_user' => $this->maxQuantityPerUser,
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
