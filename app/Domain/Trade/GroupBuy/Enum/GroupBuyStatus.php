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

namespace App\Domain\Trade\GroupBuy\Enum;

/**
 * 团购活动状态枚举.
 */
enum GroupBuyStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case SOLD_OUT = 'sold_out';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待开始',
            self::ACTIVE => '进行中',
            self::ENDED => '已结束',
            self::CANCELLED => '已取消',
            self::SOLD_OUT => '已售罄',
        };
    }

    public function canEdit(): bool
    {
        return match ($this) {
            self::PENDING, self::ACTIVE => true,
            default => false,
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::PENDING, self::ENDED, self::CANCELLED => true,
            default => false,
        };
    }
}
