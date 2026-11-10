<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;

final class NullExternalFulfillment implements ExternalFulfillmentInterface
{
    public function assertAvailable(string $provider, array $snapshot = []): void
    {
        if ($provider !== 'merchant') { throw new \DomainException('商品履约服务暂不可用，请稍后重试'); }
    }

    public function prepare(array $items, array $address, string $orderType): array
    {
        if ($items !== []) {
            throw new \DomainException('商品履约服务暂不可用，请稍后重试');
        }
        return ['items' => [], 'shipping_fee' => 0];
    }
}
