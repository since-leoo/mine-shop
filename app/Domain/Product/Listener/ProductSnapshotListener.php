<?php

declare(strict_types=1);

namespace App\Domain\Product\Listener;

use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductDeleted;
use App\Domain\Product\Event\ProductUpdated;
use App\Domain\Product\Service\ProductSnapshotService;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

final class ProductSnapshotListener implements ListenerInterface
{
    public function __construct(
        private readonly ProductSnapshotService $snapshotService,
        private readonly LoggerInterface $logger
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
                $this->snapshotService->deleteSkus($event->skuIds);
                return;
            }

            $this->snapshotService->syncProduct($event->product);

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
}
