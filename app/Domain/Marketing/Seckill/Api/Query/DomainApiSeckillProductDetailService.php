<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Seckill\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Marketing\Seckill\Repository\SeckillProductRepository;
use App\Domain\Marketing\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillProduct;
use App\Infrastructure\Model\Seckill\SeckillSession;

/**
 * 秒杀商品详情领域查询服务.
 *
 * 组合商品快照 + 秒杀活动数据，返回秒杀视角的商品详情.
 */
final class DomainApiSeckillProductDetailService
{
    public function __construct(
        private readonly ProductSnapshotInterface $productSnapshotService,
        private readonly SeckillProductRepository $seckillProductRepository,
        private readonly SeckillSessionRepository $seckillSessionRepository
    ) {}

    /**
     * 获取秒杀商品详情.
     *
     * @return null|array{product: array, seckillProduct: SeckillProduct, session: SeckillSession}
     */
    public function getDetail(int $sessionId, int $spuId): ?array
    {
        // 1. 查找场次
        $session = $this->seckillSessionRepository->findById($sessionId);
        if (! $session || ! $session->is_enabled) {
            return null;
        }

        // 2. 查找该场次下该商品的秒杀配置
        $seckillProduct = $this->findSeckillProduct($sessionId, $spuId);
        if (! $seckillProduct || ! $seckillProduct->is_enabled) {
            return null;
        }

        // 3. 获取商品基础数据（含 SKU、属性、图库）
        $product = $this->productSnapshotService->getProduct($spuId, ['skus', 'attributes', 'gallery']);
        if (! $product) {
            return null;
        }

        return [
            'product' => $product,
            'seckillProduct' => $seckillProduct,
            'session' => $session,
        ];
    }

    /**
     * 在场次中查找指定 SPU 的秒杀商品记录.
     */
    private function findSeckillProduct(int $sessionId, int $spuId): ?SeckillProduct
    {
        return SeckillProduct::where('session_id', $sessionId)
            ->where('product_id', $spuId)
            ->where('is_enabled', true)
            ->with(['product:id,name,main_image', 'productSku'])
            ->first();
    }
}
