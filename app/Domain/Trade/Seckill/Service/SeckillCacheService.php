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

namespace App\Domain\Trade\Seckill\Service;

use App\Domain\Trade\Seckill\Entity\SeckillProductEntity;
use App\Domain\Trade\Seckill\Entity\SeckillSessionEntity;
use App\Domain\Trade\Seckill\Mapper\SeckillProductMapper;
use App\Domain\Trade\Seckill\Mapper\SeckillSessionMapper;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Abstract\ICache;

/**
 * 秒杀缓存服务.
 *
 * 负责秒杀场次、商品和库存数据的 Redis 缓存管理。
 * 提供缓存预热、清理和查询功能，用于提升秒杀场景的读取性能。
 *
 * 缓存结构：
 * - seckill:session:{id} - 场次信息（String）
 * - seckill:session:{id}:products - 场次商品（Hash，field=skuId）
 * - seckill:stock:{id} - 场次库存（Hash，field=skuId，value=剩余库存）
 */
final class SeckillCacheService
{
    /**
     * 缓存 Key 前缀.
     */
    private const PREFIX = 'seckill';

    /**
     * 缓存过期时间（秒）.
     */
    private const TTL = 7200;

    public function __construct(
        private readonly ICache $cache,
        private readonly SeckillSessionRepository $sessionRepository,
        private readonly SeckillProductRepository $productRepository,
    ) {}

    /**
     * 预热场次缓存.
     *
     * 将场次信息、商品列表和库存数据加载到 Redis。
     * 通常在场次开始前调用，确保秒杀开始时数据已在缓存中。
     *
     * @param int $sessionId 场次 ID
     */
    public function warmSession(int $sessionId): void
    {
        $this->redis()->setPrefix(self::PREFIX);
        $model = $this->sessionRepository->findById($sessionId);
        if (! $model) {
            return;
        }

        $this->cache->set($this->sessionKey($sessionId), json_encode($model->toArray(), \JSON_UNESCAPED_UNICODE), ['EX' => self::TTL]);

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
        $this->warmStockHash($sessionId, $products);
    }

    /**
     * 预热活动下所有场次的缓存.
     *
     * @param int $activityId 活动 ID
     */
    public function warmActivity(int $activityId): void
    {
        foreach ($this->sessionRepository->findByActivityId($activityId) as $session) {
            $this->warmSession((int) $session->id);
        }
    }

    /**
     * 清除场次缓存.
     *
     * 删除场次信息、商品列表和库存缓存。
     * 通常在场次结束或取消时调用。
     *
     * @param int $sessionId 场次 ID
     */
    public function evictSession(int $sessionId): void
    {
        $this->cache->delete($this->sessionKey($sessionId), $this->sessionProductsKey($sessionId), \sprintf('stock:%d', $sessionId));
    }

    /**
     * 清除活动下所有场次的缓存.
     *
     * @param int $activityId 活动 ID
     */
    public function evictActivity(int $activityId): void
    {
        $this->cache->setPrefix(self::PREFIX);
        foreach ($this->sessionRepository->findByActivityId($activityId) as $session) {
            $this->evictSession((int) $session->id);
        }
    }

    /**
     * 获取场次信息.
     *
     * 优先从缓存读取，缓存未命中时从数据库加载并预热缓存。
     *
     * @param int $sessionId 场次 ID
     * @return SeckillSessionEntity|null 场次实体，不存在返回 null
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
     * 根据 SKU ID 获取秒杀商品信息.
     *
     * 优先从缓存读取，缓存未命中时从数据库加载并预热缓存。
     *
     * @param int $sessionId 场次 ID
     * @param int $skuId SKU ID
     * @return SeckillProductEntity|null 秒杀商品实体，不存在返回 null
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

    /**
     * 预热库存 Hash.
     *
     * 将场次下所有启用商品的剩余库存写入 Redis Hash。
     *
     * @param int $sessionId 场次 ID
     * @param array $products 商品列表
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
        $this->cache->delete($hashKey);
        if ($stockFields !== []) {
            $this->cache->hMset($hashKey, $stockFields);
        }
    }

    /**
     * 从缓存数据重建场次实体.
     *
     * @param array $data 缓存数据
     * @return SeckillSessionEntity 场次实体
     */
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

    /**
     * 从缓存数据重建秒杀商品实体.
     *
     * @param array $data 缓存数据
     * @return SeckillProductEntity 秒杀商品实体
     */
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

    /**
     * 生成场次缓存 Key.
     */
    private function sessionKey(int $id): string
    {
        return \sprintf('session:%d', $id);
    }

    /**
     * 生成场次商品缓存 Key.
     */
    private function sessionProductsKey(int $id): string
    {
        return \sprintf('session:%d:products', $id);
    }

    /**
     * 获取 Redis 缓存实例并设置前缀.
     */
    private function redis(): ICache
    {
        $this->cache->setPrefix(self::PREFIX);
        return $this->cache;
    }
}
