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

use Hyperf\Coroutine\Parallel;
use Plugin\SupplyChain\Domain\Service\DomainSupplyCheckoutService;

final class AppSupplyCheckoutService
{
    public function __construct(private readonly DomainSupplyCheckoutService $checkout) {}

    public function assertAvailable(string $provider, array $snapshot = []): void
    {
        $this->checkout->assertAvailable($provider, $snapshot);
    }

    public function prepare(array $items, array $address, string $type): array
    {
        $groups = $this->checkout->groups($items, $address, $type);
        $parallel = new Parallel(3);
        foreach ($groups as $connectionId => $group) {
            $parallel->add(fn () => $this->checkout->quote((int) $connectionId, $group, $address), (string) $connectionId);
        }
        return $this->checkout->combine($parallel->wait());
    }
}
