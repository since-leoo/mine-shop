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

namespace App\Domain\Marketing\GroupBuy\Enum;

/**
 * 团购活动状态枚举.
 */
enum GroupBuyStatus: string
{
    /**
     * 待开始.
     */
    case PENDING = 'pending';

    /**
     * 进行中.
     */
    case ACTIVE = 'active';

    /**
     * 已结束.
     */
    case ENDED = 'ended';

    /**
     * 已取消.
     */
    case CANCELLED = 'cancelled';

    /**
     * 已售罄.
     */
    case SOLD_OUT = 'sold_out';

    /**
     * 获取所有状态值.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * 获取状态描述.
     */
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

    /**
     * 检查是否可以编辑.
     */
    public function canEdit(): bool
    {
        return match ($this) {
            self::PENDING => true,
            self::ACTIVE => true,
            self::ENDED => false,
            self::CANCELLED => false,
            self::SOLD_OUT => false,
        };
    }

    /**
     * 检查是否可以删除.
     */
    public function canDelete(): bool
    {
        return match ($this) {
            self::PENDING => true,
            self::ACTIVE => false,
            self::ENDED => true,
            self::CANCELLED => true,
            self::SOLD_OUT => false,
        };
    }
}
