<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\RegisterInput;
use App\Interface\Api\DTO\Auth\RegisterDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

final class RegisterRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function toDto(): RegisterInput
    {
        return Mapper::map($this->validated(), new RegisterDto());
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/', Rule::unique('members', 'phone')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:6'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
