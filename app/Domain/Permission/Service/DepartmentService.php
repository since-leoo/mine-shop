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

use App\Domain\Permission\Contract\Department\DepartmentCreateInput;
use App\Domain\Permission\Contract\Department\DepartmentUpdateInput;
use App\Domain\Permission\Repository\DepartmentRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\Department;

final class DepartmentService extends IService
{
    public function __construct(
        private readonly DepartmentRepository $repository
    ) {}

    /**
     * 创建部门.
     */
    public function create(DepartmentCreateInput $input): Department
    {
        // 使用 DTO 的 toArray() 方法获取数据
        $department = $this->repository->create($input->toArray());

        // 同步关联关系
        $department->department_users()->sync($input->getDepartmentUsers());
        $department->leader()->sync($input->getLeaders());

        return $department;
    }

    /**
     * 更新部门.
     */
    public function update(DepartmentUpdateInput $input): ?Department
    {
        /** @var null|Department $department */
        $department = $this->repository->findById($input->getId());
        if (! $department) {
            return null;
        }

        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($input->getId(), $input->toArray());

        // 同步关联关系
        $department->department_users()->sync($input->getDepartmentUsers());
        $department->leader()->sync($input->getLeaders());

        return $department;
    }

    /**
     * 删除部门.
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }
}
