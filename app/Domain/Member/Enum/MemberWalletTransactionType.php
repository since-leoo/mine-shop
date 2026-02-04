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

namespace App\Domain\Member\Enum;

/**
 * 会员来源枚举.
 * 'recharge', 'consume', 'refund', 'withdraw', 'freeze', 'unfreeze', 'adjust_in', 'adjust_out'.
 */
enum MemberWalletTransactionType: string
{
    case Recharge = 'recharge';
    case Consume = 'consume';
    case Refund = 'refund';
    case Withdraw = 'withdraw';
    case Freeze = 'freeze';
    case Unfreeze = 'unfreeze';
    case AdjustIn = 'adjust_in';
    case AdjustOut = 'adjust_out';

    public static function getDescription($value): string
    {
        return match ($value) {
            self::Recharge => '充值',
            self::Consume => '消费',
            self::Refund => '退款',
            self::Withdraw => '提现',
            self::Freeze => '冻结',
            self::Unfreeze => '解冻',
            self::AdjustIn => '调整入账',
            self::AdjustOut => '调整出账',
            default => '未知',
        };
    }
}
