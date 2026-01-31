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

namespace App\Domain\Order\Factory;

use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Order\Strategy\NormalOrderStrategy;

final class OrderTypeStrategyFactory
{
    /**
     * @var array<string, OrderTypeStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(NormalOrderStrategy $normalStrategy)
    {
        if (! isset($this->strategies[$normalStrategy->type()])) {
            $this->strategies = [
                $normalStrategy->type() => $normalStrategy,
            ];
        }
    }

    public function make(string $type): OrderTypeStrategyInterface
    {
        if (! isset($this->strategies[$type])) {
            throw new \RuntimeException(\sprintf('不支持的订单类型：%s', $type));
        }

        return $this->strategies[$type];
    }
}
