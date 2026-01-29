<?php

declare(strict_types=1);

namespace App\Domain\Product\Contract;

interface ProductSnapshotInterface
{
    /**
     * @param array<int, int> $skuIds
     * @return array<int, array<string, mixed>>
     */
    public function getSkuSnapshots(array $skuIds): array;
}
