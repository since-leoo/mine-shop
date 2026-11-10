<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;

final class DomainCommerceAfterSaleService
{
    public function __construct(private readonly AfterSaleRepository $repository) {}
    public function find(int $id): ?array { return $this->repository->findDetailById($id)?->toArray(); }
    public function assertOwner(int $id, string $provider): void
    {
        DomainCommerceProductService::validateProvider($provider);
        $sale = $this->find($id);
        if ($sale === null) { throw new \RuntimeException('售后单不存在'); }
        if (($sale['order_item']['fulfillment_provider'] ?? 'merchant') !== $provider) {
            throw new \DomainException('售后单不属于当前履约能力');
        }
    }
}
