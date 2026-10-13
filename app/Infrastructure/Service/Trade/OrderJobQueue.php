<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Trade;

use App\Domain\Trade\Order\Contract\OrderJobQueueInterface;
use App\Domain\Trade\Order\Job\OrderCreateJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;

final class OrderJobQueue implements OrderJobQueueInterface
{
    public function __construct(private readonly DriverFactory $drivers) {}

    public function push(string $tradeNo): bool
    {
        return $this->drivers->get('default')->push(new OrderCreateJob($tradeNo));
    }
}
