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

/** Dispatched inside the order creation transaction so extensions persist atomically. */
final class OrderPersisted
{
    public function __construct(public readonly string $orderNo, public readonly array $payload) {}
}
