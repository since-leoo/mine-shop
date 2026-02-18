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

namespace App\Domain\Trade\Seckill\Api\Query;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Infrastructure\Model\Seckill\SeckillProduct;

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
        if (! $session || ! $session->is_enabled) {
            return null;
        }

        $seckillProduct = SeckillProduct::where('session_id', $sessionId)
            ->where('product_id', $spuId)->where('is_enabled', true)
            ->with(['product:id,name,main_image', 'productSku'])->first();
        if (! $seckillProduct) {
            return null;
        }

        $product = $this->productSnapshotService->getProduct($spuId, ['skus', 'attributes', 'gallery']);
        if (! $product) {
            return null;
        }

        return ['product' => $product, 'seckillProduct' => $seckillProduct, 'session' => $session];
    }
}
