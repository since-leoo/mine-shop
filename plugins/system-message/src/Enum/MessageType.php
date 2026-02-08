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

namespace Plugin\Since\SystemMessage\Enum;

/**
 * 消息类型枚举.
 */
enum MessageType: string
{
    case SYSTEM = 'system';           // 系统消息
    case ANNOUNCEMENT = 'announcement'; // 公告
    case ALERT = 'alert';             // 警报
    case REMINDER = 'reminder';       // 提醒
    case MARKETING = 'marketing';     // 营销

    /**
     * 获取标签.
     */
    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => '系统消息',
            self::ANNOUNCEMENT => '公告',
            self::ALERT => '警报',
            self::REMINDER => '提醒',
            self::MARKETING => '营销',
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
