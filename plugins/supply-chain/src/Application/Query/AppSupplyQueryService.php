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

namespace Plugin\SupplyChain\Application\Query;

use Plugin\SupplyChain\Domain\Service\DomainSupplyQueryService;

final class AppSupplyQueryService
{
    public function __construct(private readonly DomainSupplyQueryService $queries) {}

    public function connections(array $filters): array
    {
        return $this->queries->connections($filters);
    }

    public function catalog(array $filters): array
    {
        return $this->queries->catalog($filters);
    }

    public function catalogDetail(int $connectionId, string $externalId): array
    {
        return $this->queries->catalogDetail($connectionId, $externalId);
    }

    public function products(array $filters): array
    {
        return $this->queries->products($filters);
    }

    public function purchases(array $filters): array
    {
        return $this->queries->purchases($filters);
    }

    public function purchase(int $id): array
    {
        return $this->queries->purchase($id);
    }

    public function tasks(array $filters): array
    {
        return $this->queries->tasks($filters);
    }

    public function task(int $id): array
    {
        return $this->queries->task($id);
    }

    public function logs(array $filters): array
    {
        return $this->queries->logs($filters);
    }

    public function log(int $id): array
    {
        return $this->queries->log($id);
    }

    public function afterSales(array $filters): array
    {
        return $this->queries->afterSales($filters);
    }

    public function afterSale(int $id): array
    {
        return $this->queries->afterSale($id);
    }

    public function afterSaleTypes(int $connectionId, string $externalId): array
    {
        return $this->queries->afterSaleTypes($connectionId, $externalId);
    }
}
