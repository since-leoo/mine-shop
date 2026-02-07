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

namespace App\Application\Commad;

use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\Department\DepartmentCreateInput;
use App\Domain\Permission\Contract\Department\DepartmentUpdateInput;
use App\Domain\Permission\Service\DomainDepartmentService;
use App\Infrastructure\Model\Permission\Department;
use Hyperf\DbConnection\Db;

final class AppDepartmentCommandService
{
    public function __construct(
        private readonly DomainDepartmentService $departmentService
    ) {}

    public function create(DepartmentCreateInput $input): Department
    {
        return Db::transaction(fn () => $this->departmentService->create($input));
    }

    public function update(DepartmentUpdateInput $input): ?Department
    {
        return Db::transaction(fn () => $this->departmentService->update($input));
    }

    public function delete(DeleteInput $input): int
    {
        return $this->departmentService->delete($input->getIds());
    }
}
