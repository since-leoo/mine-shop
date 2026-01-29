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

namespace App\Application\Permission\Assembler;

use App\Domain\Shared\ValueObject\PageQuery;

final class PermissionQueryAssembler
{
    public static function toPageQuery(array $filters, int $page, int $pageSize): PageQuery
    {
        return (new PageQuery())
            ->setFilters($filters)
            ->setPage($page)
            ->setPageSize($pageSize);
    }
}
