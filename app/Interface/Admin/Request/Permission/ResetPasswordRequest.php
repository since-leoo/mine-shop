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

use App\Interface\Admin\Dto\Permission\UserResetPasswordDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

class ResetPasswordRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function resetPasswordRules(): array
    {
        return [
            'id' => 'required|integer|exists:user,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => trans('user.id'),
        ];
    }

    public function toDto(int $operatorId): UserResetPasswordDto
    {
        $params = $this->validated();
        $params['user_id'] = (int) $params['id'];
        $params['operator_id'] = $operatorId;
        return Mapper::map($params, new UserResetPasswordDto());
    }
}
