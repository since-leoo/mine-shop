<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Application\Admin;

use Plugin\Since\Seckill\Infrastructure\Model\SeckillProduct;
use Plugin\Since\Seckill\Domain\Service\DomainSeckillProductService;

final class AppSeckillProductQueryService
{
    public function __construct(private readonly DomainSeckillProductService $productService) {}

    public function page(array $filters, int $page, int $pageSize): array { return $this->productService->page($filters, $page, $pageSize); }

    public function find(int $id): ?SeckillProduct
    {
        /** @var null|SeckillProduct $product */
        $product = $this->productService->findById($id);
        $product?->load(['session', 'product', 'productSku']);
        return $product;
    }

    public function findBySessionId(int $sessionId): array { return $this->productService->findBySessionId($sessionId); }
}
