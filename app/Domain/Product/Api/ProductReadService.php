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

namespace App\Domain\Product\Api;

use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Product\Service\ProductService;
use App\Infrastructure\Model\Product\Product;

/**
 * 面向 API 场景的商品读领域服务，聚焦可对外输出的数据形态。
 */
final class ProductReadService
{
    public function __construct(
        public readonly ProductRepository $repository,
        private readonly ProductService $productService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function findDetail(int $id): ?array
    {
        $product = $this->productService->findById($id);
        if (! $product instanceof Product) {
            return null;
        }

        $product->loadMissing(['skus', 'attributes', 'gallery']);

        return $product->toArray();
    }
}
