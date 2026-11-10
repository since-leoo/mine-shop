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

namespace Plugin\SupplyChain\Interface\Adapter;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use Plugin\SupplyChain\Application\Query\AppSupplyCheckoutService;

final class SupplyFulfillmentAdapter implements ExternalFulfillmentInterface
{
    public function __construct(private readonly AppSupplyCheckoutService $service) {}

    public function assertAvailable(string $provider, array $snapshot = []): void
    {
        $this->service->assertAvailable($provider, $snapshot);
    }

    public function prepare(array $items, array $address, string $orderType): array
    {
        return $this->service->prepare($items, $address, $orderType);
    }
}
