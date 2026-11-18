<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;

use App\Infrastructure\Error\CommerceError;

final class DomainCommerceAfterSaleService
{
    public function __construct(private readonly AfterSaleRepository $repository) {}
    public function find(int $id): ?array { return $this->repository->findDetailById($id)?->toArray(); }
    public function assertOwner(int $id, string $provider): void
    {
        DomainCommerceProductService::validateProvider($provider);
        $sale = $this->find($id);
        if ($sale === null) { throw CommerceError::AFTER_SALE_NOT_FOUND->exception(); }
        if (($sale['order_item']['fulfillment_provider'] ?? 'merchant') !== $provider) {
            throw CommerceError::AFTER_SALE_PROVIDER_MISMATCH->exception();
        }
    }
}
