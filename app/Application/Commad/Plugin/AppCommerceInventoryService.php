<?php

declare(strict_types=1);

namespace App\Application\Commad\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\CommerceInventoryInterface;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceInventoryService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use App\Domain\Catalog\Product\Service\DomainProductSnapshotService;

final class AppCommerceInventoryService implements CommerceInventoryInterface
{
    public function __construct(private readonly DomainCommerceInventoryService $service, private readonly TransactionManagerInterface $transactions, private readonly DomainProductSnapshotService $snapshots) {}
    public function project(string $provider, int $skuId, int $upstreamStock, int $version): int
    {
        return $this->reconcile($provider, $skuId, $upstreamStock, $version, []);
    }
    public function reconcile(string $provider, int $skuId, int $upstreamStock, int $version, array $acknowledgedTradeNos): int
    {
        $available = $this->transactions->run(fn () => $this->service->project($provider, $skuId, $upstreamStock, $version, $acknowledgedTradeNos));
        $this->snapshots->deleteSkus([$skuId]);
        $snapshot = $this->snapshots->getSkuSnapshots([$skuId])[$skuId] ?? null;
        if ($snapshot !== null) { $this->snapshots->evictProduct((int) $snapshot['product_id']); }
        return $available;
    }
    public function protect(string $provider, string $tradeNo, array $items): void
    {
        $this->transactions->run(fn () => $this->service->protect($provider, $tradeNo, $items));
    }

    public function clearProtection(string $provider, string $tradeNo, array $items): void
    {
        $this->service->clearProtection($provider, $tradeNo, $items);
    }

    public function acknowledge(string $provider, string $tradeNo, array $items): void { $this->service->acknowledge($provider, $tradeNo, $items); }
}
