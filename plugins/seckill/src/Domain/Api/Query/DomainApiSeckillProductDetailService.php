<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Domain\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use Plugin\Since\Seckill\Infrastructure\Model\SeckillProduct;
use Plugin\Since\Seckill\Domain\Repository\SeckillProductRepository;
use Plugin\Since\Seckill\Domain\Repository\SeckillSessionRepository;

final class DomainApiSeckillProductDetailService
{
    public function __construct(
        private readonly ProductSnapshotInterface $productSnapshotService,
        private readonly SeckillProductRepository $seckillProductRepository,
        private readonly SeckillSessionRepository $seckillSessionRepository
    ) {}

    public function getDetail(int $sessionId, int $spuId): ?array
    {
        $session = $this->seckillSessionRepository->findById($sessionId);
        if (!$session || !$session->is_enabled) { return null; }

        $seckillProduct = SeckillProduct::where('session_id', $sessionId)
            ->where('product_id', $spuId)->where('is_enabled', true)
            ->with(['product:id,name,main_image', 'productSku'])->first();
        if (!$seckillProduct) { return null; }

        $product = $this->productSnapshotService->getProduct($spuId, ['skus', 'attributes', 'gallery']);
        if (!$product) { return null; }

        return ['product' => $product, 'seckillProduct' => $seckillProduct, 'session' => $session];
    }
}
