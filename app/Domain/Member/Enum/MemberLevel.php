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
 * 会员等级枚举.
 */
enum MemberLevel: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case DIAMOND = 'diamond';

    public function label(): string
    {
        return match ($this) {
            self::BRONZE => '青铜会员',
            self::SILVER => '白银会员',
            self::GOLD => '黄金会员',
            self::DIAMOND => '钻石会员',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
