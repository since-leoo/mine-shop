<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface DiyCatalogDataSourceInterface
{
    public function products(array $params, int $page, int $pageSize): array;
    public function categories(array $params): array;
    public function coupons(array $params, int $page, int $pageSize): array;
    public function seckills(array $params, int $page, int $pageSize): array;
    public function groupBuys(array $params, int $page, int $pageSize): array;
    public function storefrontProducts(array $params, int $page, int $pageSize): array;
}
