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

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Repository\PositionRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 职位领域服务.
 */
final class PositionService extends IService
{
    public function __construct(
        public readonly PositionRepository $repository
    ) {}

    /**
     * 创建职位.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->repository->create($payload);
    }

    /**
     * 更新职位.
     *
     * @param array<string, mixed> $payload
     */
    public function update(int $id, array $payload): bool
    {
        return $this->repository->updateById($id, $payload);
    }

    /**
     * 删除职位.
     *
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    /**
     * 设置数据权限.
     *
     * @param array<string, mixed> $payload
     */
    public function setDataPermission(int $id, array $payload): bool
    {
        return $this->repository->updateById($id, $payload);
    }
}
