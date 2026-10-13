<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Contract;

interface OrderSubmissionStoreInterface
{
    public function create(string $tradeNo, int $memberId, array $payload): void;
    public function find(string $tradeNo): ?array;
    public function lock(string $tradeNo): ?array;
    public function updateStatus(string $tradeNo, string $status, string $error = ''): void;
    public function pending(int $limit = 100): array;
}
