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

namespace App\Domain\Trade\Order\Contract;

use App\Domain\Trade\Order\Entity\OrderItemEntity;

/**
 * 运费计算服务接口（由运费插件实现）.
 *
 * 主应用只依赖此接口，不直接依赖插件类。
 */
interface FreightServiceInterface
{
    /**
     * 计算一组订单商品的总运费.
     *
     * @param OrderItemEntity[] $items
     * @return int 运费金额（分）
     */
    public function calculateForItems(array $items, string $province): int;
}
