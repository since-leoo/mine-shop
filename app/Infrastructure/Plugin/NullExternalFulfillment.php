<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;

use App\Infrastructure\Error\CommerceError;

final class NullExternalFulfillment implements ExternalFulfillmentInterface
{
    public function assertAvailable(string $provider, array $snapshot = []): void
    {
        if ($provider !== 'merchant') { throw CommerceError::FULFILLMENT_UNAVAILABLE->exception(); }
    }

    public function prepare(array $items, array $address, string $orderType): array
    {
        if ($items !== []) {
            throw CommerceError::FULFILLMENT_UNAVAILABLE->exception();
        }
        return ['items' => [], 'shipping_fee' => 0];
    }
}
