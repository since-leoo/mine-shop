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

namespace App\Application\Permission\Service;

use App\Domain\Permission\Repository\PositionRepository;
use App\Domain\Shared\ValueObject\PageQuery;

final class PositionQueryService
{
    public function __construct(private readonly PositionRepository $repository)
    {
    }

    public function paginate(PageQuery $query): array
    {
        return $this->repository->page(
            $query->getFilters(),
            $query->getPage(),
            $query->getPageSize()
        );
    }
}
