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

namespace Plugin\ExportCenter\Enum;

enum ExportFormat: string
{
    case EXCEL = 'excel';
    case CSV = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::EXCEL => 'Excel',
            self::CSV => 'CSV',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::EXCEL => 'xlsx',
            self::CSV => 'csv',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
        };
    }
}
