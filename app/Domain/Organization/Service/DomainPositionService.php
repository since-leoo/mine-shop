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

namespace App\Domain\Organization\Service;

use App\Domain\Organization\Contract\Position\PositionInput;
use App\Domain\Organization\Repository\PositionRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 职位领域服务.
 */
final class DomainPositionService extends IService
{
    public function __construct(
        public readonly PositionRepository $repository
    ) {}

    /**
     * 创建职位.
     */
    public function create(PositionInput $input): mixed
    {
        $payload = [
            'name' => $input->getName(),
            'dept_id' => $input->getDeptId(),
            'created_by' => $input->getCreatedBy(),
        ];
        return $this->repository->create($payload);
    }

    /**
     * 更新职位.
     */
    public function update(int $id, PositionInput $input): bool
    {
        $payload = [
            'name' => $input->getName(),
            'dept_id' => $input->getDeptId(),
            'updated_by' => $input->getUpdatedBy(),
        ];
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
