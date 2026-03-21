<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\ForgotPasswordInput;
use App\Interface\Api\DTO\Auth\ForgotPasswordDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

final class ForgotPasswordRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function toDto(): ForgotPasswordInput
    {
        return Mapper::map($this->validated(), new ForgotPasswordDto());
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:6'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
