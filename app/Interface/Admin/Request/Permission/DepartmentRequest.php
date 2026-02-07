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

namespace App\Interface\Admin\Request\Permission;

use App\Domain\Organization\Contract\Department\DepartmentCreateInput;
use App\Domain\Organization\Contract\Department\DepartmentUpdateInput;
use App\Interface\Admin\Dto\Permission\DepartmentDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class DepartmentRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function createRules(): array
    {
        return [
            'name' => 'required|string|max:60|unique:department,name',
            'parent_id' => 'sometimes|integer',
            'department_users' => 'sometimes|array',
            'department_users.*' => 'sometimes|integer',
            'leader' => 'sometimes|array',
            'leader.*' => 'sometimes|integer',
        ];
    }

    public function saveRules(): array
    {
        return [
            'name' => 'required|string|max:60|unique:department,name,' . $this->route('id'),
            'parent_id' => 'sometimes|integer',
            'department_users' => 'sometimes|array',
            'department_users.*' => 'sometimes|integer',
            'leader' => 'sometimes|array',
            'leader.*' => 'sometimes|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '部门名称',
            'parent_id' => '上级部门',
        ];
    }

    /**
     * 转换为 DTO.
     * @param null|int $id 部门ID，创建时为null，更新时传入
     */
    public function toDto(?int $id, int $operatorId): DepartmentCreateInput|DepartmentUpdateInput
    {
        $params = $this->validated();
        $params['id'] = $id;
        $params['operator_id'] = $operatorId;
        $params['department_users'] ??= [];
        $params['leader'] ??= [];

        return Mapper::map($params, new DepartmentDto());
    }
}
