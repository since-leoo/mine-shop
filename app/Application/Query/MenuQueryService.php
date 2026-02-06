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

use App\Domain\Permission\Repository\MenuRepository;
use Hyperf\Collection\Collection;

final class MenuQueryService
{
    public function __construct(public readonly MenuRepository $repository) {}

    public function list(array $filters = []): Collection
    {
        return $this->repository->list($filters);
    }
}
