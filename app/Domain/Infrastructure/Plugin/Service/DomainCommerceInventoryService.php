<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Infrastructure\Plugin\Repository\CommerceInventoryRepository;

final class DomainCommerceInventoryService
{
    public function __construct(private readonly CommerceInventoryRepository $repository) {}
    public function project(string $provider, int $skuId, int $stock, int $version, array $trades = []): int
    {
        DomainCommerceProductService::validateProvider($provider);
        if ($skuId <= 0 || $stock < 0 || $version <= 0 || $version > 9007199254740991) {
            throw new \InvalidArgumentException('库存SKU、数量或版本无效');
        }
        $trades = array_values(array_unique($trades));
        foreach ($trades as $trade) {
            if (! is_string($trade) || $trade === '') { throw new \InvalidArgumentException('对账订单号无效'); }
        }
        return $this->repository->project($provider, $skuId, $stock, $version, $trades);
    }
    public function protect(string $provider, string $tradeNo, array $items): void
    {
        DomainCommerceProductService::validateProvider($provider);
        $order = $this->repository->procurementState($tradeNo);
        if ($order === null) { throw new \DomainException('采购对应的销售订单不存在'); }
        \App\Domain\Infrastructure\Plugin\ValueObject\ProcurementEligibilityVo::assertCanProtect($order, $provider, $items);
        $this->repository->protect($provider, $tradeNo, $items);
    }

    public function clearProtection(string $provider, string $tradeNo, array $items): void
    {
        DomainCommerceProductService::validateProvider($provider);
        $this->repository->clearProtection($provider, $tradeNo, $items);
    }

    public function acknowledge(string $provider, string $tradeNo, array $items): void
    {
        DomainCommerceProductService::validateProvider($provider);
        $this->repository->acknowledge($provider, $tradeNo, $items);
    }
}
