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

namespace App\Domain\Permission\Repository;
use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Permission\Role;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

final class RoleRepository extends IRepository
{
    public function __construct(protected readonly Role $model) {}

    public function deleteByIds(array $ids): int
    {
        return $this->deleteById($ids);
    }

    public function listByCodes(array $roleCodes): Collection
    {
        return $this->list(['code' => $roleCodes]);
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(Arr::get($params, 'name'), static function (Builder $query, $name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when(Arr::get($params, 'code'), static function (Builder $query, $code) {
                $query->whereIn('code', Arr::wrap($code));
            })
            ->when(Arr::has($params, 'status'), static function (Builder $query) use ($params) {
                $query->where('status', $params['status']);
            })
            ->when(Arr::get($params, 'created_at'), static function (Builder $query, $createdAt) {
                $query->whereBetween('created_at', $createdAt);
            });
    }
}
