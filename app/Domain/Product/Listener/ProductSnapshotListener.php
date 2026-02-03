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

namespace App\Domain\Product\Listener;

use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductDeleted;
use App\Domain\Product\Event\ProductUpdated;
use App\Domain\Product\Service\ProductSnapshotService;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

final class ProductSnapshotListener implements ListenerInterface
{
    public function __construct(
        private readonly ProductSnapshotService $snapshotService,
        private readonly LoggerInterface        $logger
    ) {}

    public function listen(): array
    {
        return [
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
        ];
    }

    public function process(object $event): void
    {
        try {
            if ($event instanceof ProductDeleted) {
                $skuIds = $event->skuIds;
                if ($skuIds === []) {
                    $skuIds = $this->resolveSkuIds($event->product);
                }
                $this->snapshotService->deleteSkus($skuIds);
                $this->snapshotService->evictProduct((int) ($event->product->id ?? 0));
                return;
            }

            $this->snapshotService->rememberProduct($event->product);

            if ($event instanceof ProductUpdated && $event->deletedSkuIds !== []) {
                $this->snapshotService->deleteSkus($event->deletedSkuIds);
            }
        } catch (\Throwable $throwable) {
            $productId = property_exists($event, 'product') ? (int) ($event->product->id ?? 0) : 0;
            $this->logger->error('商品快照同步失败', [
                'event' => $event::class,
                'product_id' => $productId,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    private function resolveSkuIds(Product $product): array
    {
        $collection = $product->relationLoaded('skus')
            ? $product->skus
            : $product->skus()->get();

        if ($collection === null) {
            return [];
        }

        return $collection->pluck('id')->map(static fn ($id) => (int) $id)->all();
    }
}
