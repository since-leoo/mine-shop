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

namespace App\Application\Query;

use App\Domain\Seckill\Service\SeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

/**
 * 秒杀商品查询服务：处理所有读操作.
 */
final class SeckillProductQueryService
{
    public function __construct(private readonly SeckillProductService $productService) {}

    /**
     * 分页查询商品.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->productService->page($filters, $page, $pageSize);
    }

    /**
     * 根据ID查找商品.
     */
    public function find(int $id): ?SeckillProduct
    {
        /** @var null|SeckillProduct $product */
        $product = $this->productService->findById($id);
        $product?->load(['session', 'product', 'productSku']);
        return $product;
    }

    /**
     * 获取指定场次的商品列表.
     *
     * @return SeckillProduct[]
     */
    public function findBySessionId(int $sessionId): array
    {
        return $this->productService->findBySessionId($sessionId);
    }
}
