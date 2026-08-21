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

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\ProfileAuthorizeInput;
use App\Interface\Api\DTO\Member\ProfileAuthorizeDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

class ProfileAuthorizeRequest extends BaseRequest
{
    /**
     * 转换为 DTO.
     */
    public function toDto(): ProfileAuthorizeInput
    {
        return Mapper::map($this->validated(), new ProfileAuthorizeDto());
    }

    public function authorizeProfileRules(): array
    {
        return [
            'avatar_url' => 'nullable|url',
            'nickname' => 'nullable|string|max:60',
            'gender' => 'nullable|integer|in:0,1,2',
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar_url' => '头像',
            'nickname' => '昵称',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'avatar_url' => $this->input('avatar_url', $this->input('avatarUrl')),
            'nickname' => $this->input('nickname', $this->input('nick_name')),
        ]);
    }
}
