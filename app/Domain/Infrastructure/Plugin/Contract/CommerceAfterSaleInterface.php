<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** 供应商售后通过此端口读取客户售后及请求核心执行已授权的状态流转。 */
interface CommerceAfterSaleInterface
{
    public function find(int $afterSaleId): ?array;
    public function approve(int $afterSaleId, string $provider, ?int $amount = null, string $remark = ''): array;
    public function reject(int $afterSaleId, string $provider, string $reason): array;
    public function receive(int $afterSaleId, string $provider): array;
    public function refund(int $afterSaleId, string $provider): array;
}
