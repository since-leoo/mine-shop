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

use App\Application\Query\AppProductQueryService;
use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductDeleted;
use App\Domain\Product\Event\ProductUpdated;
use App\Domain\Product\Service\DomainProductSnapshotService;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

/**
 * 商品快照监听器：负责同步商品快照到缓存.
 */
final class ProductSnapshotListener implements ListenerInterface
{
    public function __construct(
        private readonly DomainProductSnapshotService $snapshotService,
        private readonly AppProductQueryService $queryService,
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
            match (true) {
                $event instanceof ProductCreated => $this->handleCreated($event),
                $event instanceof ProductUpdated => $this->handleUpdated($event),
                $event instanceof ProductDeleted => $this->handleDeleted($event),
            };
        } catch (\Throwable $throwable) {
            $productId = property_exists($event, 'productId') ? $event->productId : 0;
            $this->logger->error('商品快照同步失败', [
                'event' => $event::class,
                'product_id' => $productId,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * 处理商品创建事件.
     */
    private function handleCreated(ProductCreated $event): void
    {
        // 查询完整的商品信息（包含关联关系）
        $product = $this->queryService->find($event->productId);
        if ($product) {
            $this->snapshotService->rememberProduct($product);
        }
    }

    /**
     * 处理商品更新事件.
     */
    private function handleUpdated(ProductUpdated $event): void
    {
        // 根据变更信息决定是否刷新缓存
        if ($event->changes->needsCacheRefresh()) {
            $product = $this->queryService->find($event->productId);
            if ($product) {
                $this->snapshotService->rememberProduct($product);
            }
        }

        // 删除已删除的 SKU 快照
        if ($event->changes->hasSkuDeleted()) {
            $this->snapshotService->deleteSkus($event->changes->deletedSkuIds);
        }
    }

    /**
     * 处理商品删除事件.
     */
    private function handleDeleted(ProductDeleted $event): void
    {
        $this->snapshotService->evictProduct($event->productId);
        $this->snapshotService->deleteSkus($event->skuIds);
    }
}
