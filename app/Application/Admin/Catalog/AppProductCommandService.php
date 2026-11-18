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

namespace App\Application\Admin\Catalog;

use App\Domain\Catalog\Product\Contract\ProductInput;

use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Domain\Trade\Shared\Contract\TradeOutboxInterface;
use App\Infrastructure\Error\CatalogError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Interface\Common\ResultCode;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Psr\Log\LoggerInterface;

/**
 * 商品命令服务：处理所有写操作.
 */
final class AppProductCommandService
{
    public function __construct(
        private readonly DomainProductService $productService,
        private readonly AppProductQueryService $queryService,
        private readonly TradeOutboxInterface $outbox,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * 创建商品.
     */
    public function create(ProductInput $input): Product
    {
        [$model, $event] = Db::transaction(function () use ($input) {
            $entity = $this->productService->create($input);
            $model = $this->queryService->find($entity->getId());
            if ($model === null) {
                throw new BusinessException(CatalogError::PRODUCT_CREATE_FAILED, resultCode: ResultCode::FAIL);
            }
            $event = new ProductCreated(
                productId: $entity->getId(),
                skuIds: $this->extractSkuIds($model),
                stockData: $entity->getStockData(),
            );
            $this->outbox->append('catalog-product:' . $event->operationId, 'catalog.product.created', get_object_vars($event));
            return [$model, $event];
        });
        $this->publish($event);

        return $model;
    }

    /**
     * 更新商品.
     */
    public function update(ProductInput $input): bool
    {
        $event = Db::transaction(function () use ($input) {
            $changes = $this->productService->update($input);
            $entity = $this->productService->getEntity($input->getId());
            $event = new ProductUpdated(
                productId: $input->getId(),
                changes: $changes,
                stockData: $entity->getStockData(),
            );
            $this->outbox->append('catalog-product:' . $event->operationId, 'catalog.product.updated', get_object_vars($event));
            return $event;
        });
        $this->publish($event);

        return true;
    }

    /**
     * 删除商品.
     */
    public function delete(int $id): bool
    {
        $event = Db::transaction(function () use ($id) {
            $skuIds = $this->productService->delete($id);
            $event = new ProductDeleted(productId: $id, skuIds: $skuIds);
            $this->outbox->append('catalog-product:' . $event->operationId, 'catalog.product.deleted', get_object_vars($event));
            return $event;
        });
        $this->publish($event);

        return true;
    }

    #[Transactional]
    public function updateSort(array $sortData): bool
    {
        return $this->productService->updateSort($sortData);
    }

    private function publish(ProductCreated|ProductDeleted|ProductUpdated $event): void
    {
        // 外层事务尚未提交时由 outbox 后续分发，避免发布最终回滚的变更。
        if (Db::connection()->transactionLevel() > 0) {
            return;
        }
        try {
            event($event);
        } catch (\Throwable $error) {
            $this->logger->warning('商品事件等待持久化事件重试', ['operation_id' => $event->operationId, 'product_id' => $event->productId, 'error' => $error->getMessage()]);
        }
    }

    /**
     * 提取 SKU IDs.
     *
     * @return array<int, int>
     */
    private function extractSkuIds(Product $product): array
    {
        $product->loadMissing('skus');

        return $product->skus->pluck('id')->map(static fn ($id) => (int) $id)->all();
    }
}
