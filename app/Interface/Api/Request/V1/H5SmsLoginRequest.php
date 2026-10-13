<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\H5SmsLoginInput;
use App\Interface\Api\DTO\Auth\H5SmsLoginDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

final class H5SmsLoginRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function toDto(?string $ip = null): H5SmsLoginInput
    {
        $payload = $this->validated();
        $payload['ip'] = $ip;
        return Mapper::map($payload, new H5SmsLoginDto());
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }
}
