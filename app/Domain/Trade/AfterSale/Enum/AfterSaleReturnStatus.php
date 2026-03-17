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

enum AfterSaleReturnStatus: string
{
    case NOT_REQUIRED = 'not_required';
    case PENDING = 'pending';
    case BUYER_SHIPPED = 'buyer_shipped';
    case SELLER_RECEIVED = 'seller_received';
    case SELLER_RESHIPPED = 'seller_reshipped';
    case BUYER_RECEIVED = 'buyer_received';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
