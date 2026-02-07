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

namespace App\Domain\Trade\Order\Event;

use App\Domain\Trade\Order\Entity\OrderCancelEntity;
use App\Domain\Trade\Order\Entity\OrderEntity;

final class OrderCancelledEvent
{
    public function __construct(
        public readonly OrderEntity $order,
        public readonly OrderCancelEntity $command
    ) {}
}
