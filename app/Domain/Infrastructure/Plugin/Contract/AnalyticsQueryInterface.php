<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface AnalyticsQueryInterface
{
    public function welcome(): array;
    public function analysis(string $startDate, string $endDate): array;
    public function report(string $startDate, string $endDate): array;
}
