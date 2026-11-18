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

namespace App\Domain\Trade\Order\Factory;

use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Infrastructure\Error\OrderError;

final class OrderTypeStrategyFactory
{
    /**
     * @var array<string, OrderTypeStrategyInterface>
     */
    private array $strategies = [];

    /**
     * @param OrderTypeStrategyInterface[] $strategies
     */
    public function __construct(array $strategies = [])
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->type()] = $strategy;
        }
    }

    /**
     * 动态注册策略（供插件在 boot() 中调用）.
     */
    public function register(OrderTypeStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->type()] = $strategy;
    }

    public function make(string $type): OrderTypeStrategyInterface
    {
        return $this->strategies[$type]
            ?? throw OrderError::TYPE_UNSUPPORTED->exception([$type]);
    }
}
