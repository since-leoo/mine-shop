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
 */
enum MemberSource: string
{
    case WECHAT = 'wechat';
    case MINI_PROGRAM = 'mini_program';
    case H5 = 'h5';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::WECHAT => '微信公众号',
            self::MINI_PROGRAM => '小程序',
            self::H5 => 'H5 活动页',
            self::ADMIN => '后台导入',
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
