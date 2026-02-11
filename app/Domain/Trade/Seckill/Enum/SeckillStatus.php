<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\Enum;

enum SeckillStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case ENDED = 'ended';
    case SOLD_OUT = 'sold_out';
    case CANCELLED = 'cancelled';
}
