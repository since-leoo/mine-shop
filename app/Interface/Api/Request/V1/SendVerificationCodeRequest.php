<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\VerificationCodeSendInput;
use App\Interface\Api\DTO\Auth\VerificationCodeSendDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;
use Hyperf\Validation\Rule;

final class SendVerificationCodeRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function toDto(): VerificationCodeSendInput
    {
        return Mapper::map($this->validated(), new VerificationCodeSendDto());
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'scene' => ['required', 'string', Rule::in(['register', 'forgot_password'])],
        ];
    }
}
