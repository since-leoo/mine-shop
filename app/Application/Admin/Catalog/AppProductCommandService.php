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

use App\Application\Admin\Catalog\AppProductQueryService;
use App\Domain\Catalog\Product\Contract\ProductInput;
use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Product\Product;
use App\Interface\Common\ResultCode;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;

/**
 * 商品命令服务：处理所有写操作.
 */
final class AppProductCommandService
{
    public function __construct(
        private readonly DomainProductService $productService,
        private readonly AppProductQueryService $queryService,
    ) {}

    /**
     * 创建商品.
     */
    public function create(ProductInput $input): Product
    {
        $entity = $this->productService->create($input);
        $model = $this->queryService->find($entity->getId());
        if (! $entity->getId()) {
            throw new BusinessException(ResultCode::FAIL, '创建商品失败');
        }

        // 3. 发布领域事件
        event(new ProductCreated(
            productId: $entity->getId(),
            skuIds: $this->extractSkuIds($model),
            stockData: $entity->getStockData()
        ));

        return $model;
    }

    /**
     * 更新商品.
     */
    public function update(ProductInput $input): bool
    {
        $changes = $this->productService->update($input);
        $entity = $this->productService->getEntity($input->getId());

        event(new ProductUpdated(
            productId: $input->getId(),
            changes: $changes,
            stockData: $entity->getStockData()
        ));

        return true;
    }

    /**
     * 删除商品.
     */
    public function delete(int $id): bool
    {
        // 1. 查询商品
        $product = $this->queryService->find($id);
        if (! $product) {
            throw new BusinessException(ResultCode::FAIL, '商品不存在');
        }

        // 2. 提取 SKU IDs
        $skuIds = $this->extractSkuIds($product);

        // 3. 事务管理
        Db::transaction(fn () => $this->productService->delete($id));

        // 4. 发布领域事件
        $this->dispatcher->dispatch(new ProductDeleted(
            productId: $id,
            skuIds: $skuIds
        ));

        return true;
    }

    #[Transactional]
    public function updateSort(array $sortData): bool
    {
        return $this->productService->updateSort($sortData);
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
