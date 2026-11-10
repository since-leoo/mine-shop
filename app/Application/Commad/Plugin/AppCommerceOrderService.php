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

namespace App\Application\Commad\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use App\Domain\Infrastructure\Plugin\Service\DomainCommerceOrderService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;

final class AppCommerceOrderService implements CommerceOrderInterface
{
    public function __construct(private readonly DomainCommerceOrderService $service, private readonly TransactionManagerInterface $transactions) {}

    public function find(int $orderId): ?array
    {
        return $this->service->find($orderId);
    }

    public function findByNumber(string $orderNo): ?array
    {
        return $this->service->findByNumber($orderNo);
    }

    public function paidSince(string $provider, int $afterId, int $limit = 100): array
    {
        return $this->service->paidSince($provider, $afterId, $limit);
    }

    public function ship(int $orderId, string $provider, string $shipmentKey, array $package, array $items): array
    {
        return $this->transactions->run(fn () => $this->service->ship($orderId, $provider, $shipmentKey, $package, $items));
    }
}
