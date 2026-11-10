<?php

declare(strict_types=1);

namespace App\Application\Commad\Plugin;

use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;
use App\Domain\Infrastructure\Plugin\Contract\CommerceProductInterface;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceProductService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;

/** 核心应用端口负责事务及缓存；商品业务决策仍在领域服务。 */
final class AppCommerceProductService implements CommerceProductInterface
{
    public function __construct(private readonly DomainCommerceProductService $service, private readonly TransactionManagerInterface $transactions, private readonly DomainProductSnapshotService $snapshots) {}

    public function import(string $provider, string $idempotencyKey, array $product): array
    {
        $result = $this->transactions->run(fn () => $this->service->import($provider, $idempotencyKey, $product));
        $this->invalidate($result);
        return $result;
    }

    public function find(int $productId): ?array { return $this->service->find($productId); }

    public function appendSkus(int $productId, string $provider, array $skus): array
    {
        $result = $this->transactions->run(fn () => $this->service->appendSkus($productId, $provider, $skus));
        $this->invalidate($result);
        return $result;
    }

    public function sync(int $productId, string $provider, array $changes): array
    {
        $result = $this->transactions->run(fn () => $this->service->sync($productId, $provider, $changes));
        $this->invalidate($result);
        return $result;
    }

    private function invalidate(array $product): void
    {
        $this->snapshots->evictProduct((int) $product['product_id']);
        $this->snapshots->deleteSkus(array_column($product['skus'], 'sku_id'));
    }
}
