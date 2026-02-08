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

namespace App\Domain\Marketing\Seckill\Service;

use App\Domain\Marketing\Seckill\Entity\SeckillProductEntity;
use App\Domain\Marketing\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Marketing\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Marketing\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Marketing\Seckill\Repository\SeckillProductRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\ICache;

/**
 * 秒杀缓存服务.
 *
 * Redis 结构：
 * - seckill:session:{id}          → JSON（场次数据）
 * - seckill:session:{id}:products → Hash field=sku_id value=JSON（商品数据）
 *
 * 写入：定时任务预热 + 后台变更时刷新
 * 读取：SeckillOrderStrategy 下单校验
 */
final class SeckillCacheService
{
    private const PREFIX = 'seckill';

    private const TTL = 7200;

    public function __construct(
        private readonly ICache $cache,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
    ) {
        $this->cache->setPrefix(self::PREFIX);
    }

    /**
     * 预热指定场次（场次 + 商品列表）.
     */
    public function warmSession(int $sessionId): void
    {
        $this->cache->setPrefix(self::PREFIX);

        $model = $this->sessionRepository->findById($sessionId);
        if (! $model) {
            return;
        }

        $this->cache->set(
            $this->sessionKey($sessionId),
            json_encode($model->toArray(), \JSON_UNESCAPED_UNICODE),
            ['EX' => self::TTL]
        );

        $products = $this->productRepository->findBySessionId($sessionId);
        $productsKey = $this->sessionProductsKey($sessionId);
        $this->cache->delete($productsKey);

        $fields = [];
        foreach ($products as $product) {
            $fields[(string) $product->product_sku_id] = json_encode(
                SeckillProductMapper::fromModel($product)->toArray() + ['id' => (int) $product->id],
                \JSON_UNESCAPED_UNICODE
            );
        }
        if ($fields !== []) {
            $this->cache->hMset($productsKey, $fields);
        }

        // 写入秒杀库存 Hash（供 DomainOrderStockService Lua 脚本原子扣减）
        $this->warmStockHash($sessionId, $products);
    }

    /**
     * 将秒杀商品的可用库存写入独立的库存 Hash.
     *
     * Hash key 与 DomainOrderStockService 约定一致：seckill:stock:{sessionId}
     * Hash field: sku_id → 剩余库存（quantity - sold_quantity）
     *
     * @param \App\Infrastructure\Model\Seckill\SeckillProduct[] $products
     */
    private function warmStockHash(int $sessionId, array $products): void
    {
        $hashKey = \sprintf('stock:%d', $sessionId);

        $stockFields = [];
        foreach ($products as $product) {
            if (! $product->is_enabled) {
                continue;
            }
            $remaining = max(0, (int) $product->quantity - (int) $product->sold_quantity);
            $stockFields[(string) $product->product_sku_id] = (string) $remaining;
        }

        // 先清再写
        $this->cache->delete($hashKey);
        if ($stockFields !== []) {
            $this->cache->hMset($hashKey, $stockFields);
        }
    }

    /**
     * 预热活动下所有场次.
     */
    public function warmActivity(int $activityId): void
    {
        foreach ($this->sessionRepository->findByActivityId($activityId) as $session) {
            $this->warmSession((int) $session->id);
        }
    }

    /**
     * 清除场次缓存.
     */
    public function evictSession(int $sessionId): void
    {
        $this->cache->delete(
            $this->sessionKey($sessionId),
            $this->sessionProductsKey($sessionId),
            \sprintf('stock:%d', $sessionId)
        );
    }

    /**
     * 清除活动下所有场次缓存.
     */
    public function evictActivity(int $activityId): void
    {
        $this->cache->setPrefix(self::PREFIX);
        foreach ($this->sessionRepository->findByActivityId($activityId) as $session) {
            $this->evictSession((int) $session->id);
        }
    }

    /**
     * 获取场次实体（优先缓存）.
     */
    public function getSession(int $sessionId): ?SeckillSessionEntity
    {
        $json = $this->cache->get($this->sessionKey($sessionId));

        if (\is_string($json) && $json !== '') {
            $data = json_decode($json, true);
            if (\is_array($data)) {
                return $this->hydrateSession($data);
            }
        }

        $model = $this->sessionRepository->findById($sessionId);
        if (! $model) {
            return null;
        }

        $this->warmSession($sessionId);

        return SeckillSessionMapper::fromModel($model);
    }

    /**
     * 获取场次下指定 SKU 的秒杀商品（优先缓存）.
     */
    public function getProductBySkuId(int $sessionId, int $skuId): ?SeckillProductEntity
    {
        $json = $this->cache->hGet($this->sessionProductsKey($sessionId), (string) $skuId);

        if (\is_string($json) && $json !== '') {
            $data = json_decode($json, true);
            if (\is_array($data)) {
                return $this->hydrateProduct($data);
            }
        }

        $models = $this->productRepository->findBySessionId($sessionId);
        foreach ($models as $model) {
            if ((int) $model->product_sku_id === $skuId) {
                $this->warmSession($sessionId);
                return SeckillProductMapper::fromModel($model);
            }
        }

        return null;
    }

    private function hydrateSession(array $data): SeckillSessionEntity
    {
        return SeckillSessionEntity::reconstitute(
            id: (int) ($data['id'] ?? 0),
            activityId: (int) ($data['activity_id'] ?? 0),
            startTime: (string) ($data['start_time'] ?? ''),
            endTime: (string) ($data['end_time'] ?? ''),
            status: (string) ($data['status'] ?? 'pending'),
            maxQuantityPerUser: (int) ($data['max_quantity_per_user'] ?? 1),
            totalQuantity: (int) ($data['total_quantity'] ?? 0),
            soldQuantity: (int) ($data['sold_quantity'] ?? 0),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isEnabled: (bool) ($data['is_enabled'] ?? false),
            rulesData: \is_array($data['rules'] ?? null) ? $data['rules'] : null,
            remark: $data['remark'] ?? null,
        );
    }

    private function hydrateProduct(array $data): SeckillProductEntity
    {
        return SeckillProductEntity::reconstitute(
            id: (int) ($data['id'] ?? 0),
            activityId: (int) ($data['activity_id'] ?? 0),
            sessionId: (int) ($data['session_id'] ?? 0),
            productId: (int) ($data['product_id'] ?? 0),
            productSkuId: (int) ($data['product_sku_id'] ?? 0),
            originalPrice: (int) ($data['original_price'] ?? 0),
            seckillPrice: (int) ($data['seckill_price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 0),
            soldQuantity: (int) ($data['sold_quantity'] ?? 0),
            maxQuantityPerUser: (int) ($data['max_quantity_per_user'] ?? 1),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isEnabled: (bool) ($data['is_enabled'] ?? false),
        );
    }

    private function sessionKey(int $id): string
    {
        return \sprintf('session:%d', $id);
    }

    private function sessionProductsKey(int $id): string
    {
        return \sprintf('session:%d:products', $id);
    }
}
