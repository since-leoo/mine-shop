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
 * 消息状态枚举.
 */
enum MessageStatus: string
{
    case DRAFT = 'draft';         // 草稿
    case SCHEDULED = 'scheduled'; // 已调度
    case SENDING = 'sending';     // 发送中
    case SENT = 'sent';           // 已发送
    case FAILED = 'failed';       // 发送失败

    /**
     * 获取标签.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::SCHEDULED => '已调度',
            self::SENDING => '发送中',
            self::SENT => '已发送',
            self::FAILED => '发送失败',
        };
    }

    /**
     * 是否可以发送
     */
    public function canSend(): bool
    {
        return \in_array($this, [self::DRAFT, self::SCHEDULED, self::FAILED], true);
    }

    /**
     * 获取所有状态
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
