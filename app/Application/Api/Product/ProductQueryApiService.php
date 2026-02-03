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

namespace App\Application\Api\Product;

use App\Domain\Product\Api\ProductReadService;
use App\Domain\Product\Contract\ProductSnapshotInterface;

/**
 * 小程序/前台商品读应用服务.
 */
final class ProductQueryApiService
{
    public function __construct(
        private readonly ProductReadService $productReadService,
        private readonly ProductTransformer $transformer,
        private readonly ProductSnapshotInterface $productSnapshotService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function list(array $filters, int $page = 1, int $pageSize = 20): array
    {
        /** @var array{list: array<int, array<string, mixed>>, total: int} $result */
        $result = $this->productReadService->paginate($filters, $page, $pageSize);
        $list = array_map(
            fn (array $product): array => $this->transformer->transformListItem($product),
            $result['list'],
        );

        return [
            'list' => $list,
            'total' => $result['total'],
        ];
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->productSnapshotService->getProduct($id, ['skus', 'attributes', 'gallery']);
    }
}
