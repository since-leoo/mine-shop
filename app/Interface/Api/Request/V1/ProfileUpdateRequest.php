<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\ProfileUpdateInput;
use App\Interface\Api\DTO\Member\ProfileUpdateDto;
use App\Interface\Common\Request\BaseRequest;
use Hyperf\DTO\Mapper;

class ProfileUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'nickname' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', 'integer', 'in:0,1,2'],
            'phone' => ['nullable', 'string', 'regex:/^1[3-9]\d{9}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar_url' => '头像',
            'nickname' => '昵称',
            'gender' => '性别',
            'phone' => '手机号',
        ];
    }

    public function toDto(): ProfileUpdateInput
    {
        return Mapper::map($this->validated(), new ProfileUpdateDto());
    }
}
