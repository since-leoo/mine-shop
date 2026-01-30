<?php

declare(strict_types=1);

namespace App\Domain\Order\Factory;

use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Order\Strategy\NormalOrderStrategy;
use RuntimeException;

final class OrderTypeStrategyFactory
{
    /**
     * @var array<string, OrderTypeStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(NormalOrderStrategy $normalStrategy) {
        if (! isset($this->strategies[$normalStrategy->type()])) {
            $this->strategies = [
                $normalStrategy->type() => $normalStrategy
            ];
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
