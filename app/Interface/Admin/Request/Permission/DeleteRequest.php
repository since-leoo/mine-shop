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

use App\Interface\Admin\Dto\Permission\DeleteDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

/**
 * 删除操作 Request.
 */
class DeleteRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function deleteRules(): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'ID列表',
        ];
    }

    public function toDto(int $operatorId): DeleteDto
    {
        $params = $this->validated();
        $params['operator_id'] = $operatorId;
        return Mapper::map($params, new DeleteDto());
    }
}
