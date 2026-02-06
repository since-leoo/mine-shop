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

namespace App\Application\Mapper;

use App\Domain\Shared\ValueObject\PageQuery;

final class LogQueryAssembler
{
    public static function page(array $filters, int $page, int $pageSize): PageQuery
    {
        return (new PageQuery())
            ->setFilters($filters)
            ->setPage($page)
            ->setPageSize($pageSize);
    }
}
