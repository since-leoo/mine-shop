<?php

declare(strict_types=1);

namespace App\Domain\Order\Factory;

use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use RuntimeException;

final class OrderTypeStrategyFactory
{
    /**
     * @var array<string, OrderTypeStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(OrderTypeStrategyInterface ...$strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->type()] = $strategy;
        }
    }

    public function make(string $type): OrderTypeStrategyInterface
    {
        if (! isset($this->strategies[$type])) {
            throw new RuntimeException(sprintf('不支持的订单类型：%s', $type));
        }

        return $this->strategies[$type];
    }
}
