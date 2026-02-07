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

use App\Interface\Admin\Dto\Permission\PositionDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class PositionRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function createRules(): array
    {
        return [
            'name' => 'required|string|max:50|unique:position,name',
            'dept_id' => 'required|integer|exists:department,id',
        ];
    }

    public function saveRules(): array
    {
        return [
            'name' => 'required|string|max:50|unique:position,name,' . $this->route('id'),
            'dept_id' => 'required|integer|exists:department,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '岗位名称',
            'dept_id' => '部门ID',
        ];
    }

    public function toDto(?int $id, int $operatorId): PositionDto
    {
        $params = $this->validated();
        $params['id'] = $id ?? 0;
        $params['created_by'] = $operatorId;
        $params['updated_by'] = $operatorId;
        return Mapper::map($params, new PositionDto());
    }
}
