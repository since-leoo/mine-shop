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

namespace App\Domain\Infrastructure\SystemMessage\Enum;

/**
 * 收件人类型枚举.
 */
enum RecipientType: string
{
    case ALL = 'all';               // 所有用户
    case USER = 'user';             // 指定用户
    case ROLE = 'role';             // 指定角色
    case DEPARTMENT = 'department'; // 指定部门

    /**
     * 获取标签.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => '所有用户',
            self::USER => '指定用户',
            self::ROLE => '指定角色',
            self::DEPARTMENT => '指定部门',
        };
    }

    /**
     * 获取所有类型.
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn ($case) => $case->label(), self::cases())
        );
    }

    /**
     * 获取所有值
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
