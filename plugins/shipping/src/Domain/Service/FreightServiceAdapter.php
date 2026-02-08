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

namespace Plugin\Since\Shipping\Domain\Service;

use App\Domain\Trade\Order\Contract\FreightServiceInterface;

/**
 * 运费服务适配器：实现主应用定义的接口.
 */
final class FreightServiceAdapter implements FreightServiceInterface
{
    public function __construct(
        private readonly FreightCalculationService $freightCalculationService,
    ) {}

    public function calculateForItems(array $items, string $province): int
    {
        return $this->freightCalculationService->calculateForItems($items, $province);
    }
}
