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
 * 消息渠道枚举.
 */
enum MessageChannel: string
{
    case DATABASE = 'database';     // 数据库存储（默认）
    case SOCKETIO = 'socketio';     // Socket.IO 实时推送
    case WEBSOCKET = 'websocket';   // WebSocket 实时推送
    case EMAIL = 'email';           // 邮件
    case SMS = 'sms';               // 短信
    case PUSH = 'push';             // 推送通知
    case MINIAPP = 'miniapp';       // 小程序

    /**
     * 获取标签.
     */
    public function label(): string
    {
        return match ($this) {
            self::DATABASE => '站内信',
            self::SOCKETIO => 'Socket.IO',
            self::WEBSOCKET => 'WebSocket',
            self::EMAIL => '邮件',
            self::SMS => '短信',
            self::PUSH => '推送通知',
            self::MINIAPP => '小程序',
        };
    }

    /**
     * 获取所有渠道.
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

    /**
     * 获取默认渠道.
     */
    public static function defaults(): array
    {
        return [self::DATABASE->value];
    }
}
