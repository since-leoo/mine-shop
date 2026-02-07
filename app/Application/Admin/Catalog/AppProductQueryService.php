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

use App\Domain\Catalog\Product\Service\DomainProductService;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;

/**
 * 商品查询服务：处理所有读操作.
 */
final class AppProductQueryService
{
    public function __construct(private readonly DomainProductService $productService) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->productService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?Product
    {
        return $this->productService->findById($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('status', Product::STATUS_ACTIVE)->count(),
            'draft' => Product::query()->where('status', Product::STATUS_DRAFT)->count(),
            'inactive' => Product::query()->where('status', Product::STATUS_INACTIVE)->count(),
            'sold_out' => Product::query()->where('status', Product::STATUS_SOLD_OUT)->count(),
            'warning_stock' => ProductSku::query()->whereRaw('stock <= warning_stock')->count(),
        ];
    }
}
