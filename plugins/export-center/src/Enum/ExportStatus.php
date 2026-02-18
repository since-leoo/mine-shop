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

enum ExportStatus: string
{
    case PENDING = 'pending';       // 待处理
    case PROCESSING = 'processing'; // 处理中
    case SUCCESS = 'success';       // 成功
    case FAILED = 'failed';         // 失败
    case EXPIRED = 'expired';       // 已过期

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::SUCCESS => '成功',
            self::FAILED => '失败',
            self::EXPIRED => '已过期',
        };
    }
}
