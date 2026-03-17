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

namespace App\Domain\Trade\AfterSale\Enum;

enum AfterSaleStatus: string
{
    case PENDING_REVIEW = 'pending_review';
    case WAITING_BUYER_RETURN = 'waiting_buyer_return';
    case WAITING_SELLER_RECEIVE = 'waiting_seller_receive';
    case WAITING_REFUND = 'waiting_refund';
    case REFUNDING = 'refunding';
    case WAITING_RESHIP = 'waiting_reship';
    case RESHIPPED = 'reshipped';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
