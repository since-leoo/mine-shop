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

namespace App\Application\Query;

use App\Domain\Permission\Repository\LeaderRepository;
use App\Domain\Shared\ValueObject\PageQuery;

final class LeaderQueryService
{
    public function __construct(public readonly LeaderRepository $repository) {}

    public function paginate(PageQuery $query): array
    {
        return $this->repository->page(
            $query->getFilters(),
            $query->getPage(),
            $query->getPageSize()
        );
    }
}
