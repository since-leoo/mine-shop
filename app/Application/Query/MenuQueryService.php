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

use App\Domain\Permission\Service\MenuService;
use Hyperf\Collection\Collection;

final class MenuQueryService
{
    public function __construct(private readonly MenuService $menuService) {}

    public function list(array $filters = []): Collection
    {
        return $this->menuService->repository->list($filters);
    }
}
