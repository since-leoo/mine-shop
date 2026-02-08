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

namespace Plugin\Since\Seckill\Domain\Enum;

enum SeckillStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case ENDED = 'ended';
    case SOLD_OUT = 'sold_out';
    case CANCELLED = 'cancelled';
}
