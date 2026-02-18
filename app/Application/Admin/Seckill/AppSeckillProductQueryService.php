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

namespace App\Application\Admin\Seckill;

use App\Domain\Trade\Seckill\Service\DomainSeckillProductService;
use App\Infrastructure\Model\Seckill\SeckillProduct;

final class AppSeckillProductQueryService
{
    public function __construct(private readonly DomainSeckillProductService $productService) {}

    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->productService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?SeckillProduct
    {
        /** @var null|SeckillProduct $product */
        $product = $this->productService->findById($id);
        $product?->load(['session', 'product', 'productSku']);
        return $product;
    }

    public function findBySessionId(int $sessionId): array
    {
        return $this->productService->findBySessionId($sessionId);
    }
}
