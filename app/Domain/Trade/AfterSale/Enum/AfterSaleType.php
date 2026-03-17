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

enum AfterSaleType: string
{
    case REFUND_ONLY = 'refund_only';
    case RETURN_REFUND = 'return_refund';
    case EXCHANGE = 'exchange';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
