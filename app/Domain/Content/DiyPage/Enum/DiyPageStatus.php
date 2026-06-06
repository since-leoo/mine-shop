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

namespace App\Domain\Content\DiyPage\Enum;

final class DiyPageStatus
{
    public const PAGE_DRAFT = 'draft';

    public const PAGE_PUBLISHED = 'published';

    public const PAGE_DISABLED = 'disabled';

    public const VERSION_DRAFT = 'draft';

    public const VERSION_PUBLISHED = 'published';

    public const VERSION_ARCHIVED = 'archived';

    public const TYPE_MINIPROGRAM = 'miniprogram';

    public const TYPE_H5 = 'h5';

    public const TYPE_ALL = 'all';

    /**
     * @return string[]
     */
    public static function pageTypes(): array
    {
        return [
            self::TYPE_MINIPROGRAM,
            self::TYPE_H5,
            self::TYPE_ALL,
        ];
    }
}
