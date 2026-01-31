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

namespace App\Domain\Order\Event;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderShipEntity;

final class OrderShippedEvent
{
    public function __construct(
        public readonly OrderEntity $order,
        public readonly OrderShipEntity $command
    ) {}
}
