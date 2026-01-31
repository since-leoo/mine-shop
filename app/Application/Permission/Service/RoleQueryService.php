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

use App\Domain\Permission\Repository\RoleRepository;
use App\Domain\Shared\ValueObject\PageQuery;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\Collection\Collection;

final class RoleQueryService
{
    public function __construct(private readonly RoleRepository $repository) {}

    public function paginate(PageQuery $query): array
    {
        return $this->repository->page(
            $query->getFilters(),
            $query->getPage(),
            $query->getPageSize()
        );
    }

    public function list(array $filters = []): Collection
    {
        return $this->repository->list($filters);
    }

    public function find(int $id): ?Role
    {
        return $this->repository->findById($id);
    }

    public function permissions(int $id): Collection
    {
        $role = $this->repository->findById($id);
        if (! $role) {
            return new Collection();
        }
        return $role->menus()->get();
    }
}
