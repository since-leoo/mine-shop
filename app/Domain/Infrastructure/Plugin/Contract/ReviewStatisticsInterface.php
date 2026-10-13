<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface ReviewStatisticsInterface
{
    public function getStatistics(): array;
}
