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

/**
 * 订单发货输入契约接口.
 */
interface OrderShipInput
{
    public function getOrderId(): int;

    public function getOperatorId(): int;

    public function getOperatorName(): string;

    public function getPackages(): array;
}
