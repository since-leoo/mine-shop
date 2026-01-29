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
 * 分类状态枚举.
 */
enum CategoryStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function getText(): string
    {
        return match ($this) {
            self::ACTIVE => '启用',
            self::INACTIVE => '禁用',
        };
    }

    public static function getLabel(string $value): string
    {
        return self::from($value)->getText();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function getOptions(): array
    {
        return [
            ['value' => self::ACTIVE->value, 'label' => self::ACTIVE->getText()],
            ['value' => self::INACTIVE->value, 'label' => self::INACTIVE->getText()],
        ];
    }
}
