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

use App\Domain\Permission\Contract\Leader\LeaderCreateInput;
use App\Interface\Admin\DTO\Permission\LeaderCreateDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;

class LeaderRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function createRules(): array
    {
        return [
            'user_id' => 'required|array',
            'dept_id' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => '用户ID',
            'dept_id' => '部门ID',
        ];
    }

    /**
     * 转换为 DTO.
     */
    public function toDto(int $operatorId): LeaderCreateInput
    {
        $dto = new LeaderCreateDto();
        $dto->dept_id = (int) $this->input('dept_id');
        $dto->user_ids = (array) $this->input('user_id', []);
        $dto->operator_id = $operatorId;

        return $dto;
    }
}
