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

namespace App\Domain\Product\Enum;

/**
 * 商品状态枚举.
 */
enum ProductStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SOLD_OUT = 'sold_out';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT->value,
            self::ACTIVE->value,
            self::INACTIVE->value,
            self::SOLD_OUT->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function mutableValues(): array
    {
        return [
            self::DRAFT->value,
            self::ACTIVE->value,
            self::INACTIVE->value,
        ];
    }
}
