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

namespace App\Infrastructure\Abstract;

use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Model;
use Hyperf\DbConnection\Traits\HasContainer;

/**
 * @template T of Model
 * @property IRepository<T> $repository
 */
abstract class IService
{
    use HasContainer;

    public function count(array $params): int
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->count($params);
    }

    public function page(array $params, int $page = 1, int $pageSize = 10): array
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->page($params, $page, $pageSize);
    }

    public function getList(array $paras): Collection
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->list($paras);
    }

    /**
     * @return bool
     */
    public function updateById(mixed $id, array $data): mixed
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->updateById($id, $data);
    }

    public function deleteById(mixed $id): int
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->deleteById($id);
    }

    /**
     * @return null|T
     */
    public function findById(mixed $id): mixed
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->findById($id);
    }

    public function existsById(mixed $id): bool
    {
        /** @var static&object{repository: IRepository<T>} $this */
        return $this->repository->existsById($id);
    }
}
