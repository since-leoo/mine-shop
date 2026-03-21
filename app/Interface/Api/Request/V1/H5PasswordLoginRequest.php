<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1;

use App\Domain\Member\Contract\H5LoginInput;
use App\Interface\Api\DTO\Auth\H5LoginDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\DTO\Mapper;

final class H5PasswordLoginRequest extends BaseRequest
{
    use NoAuthorizeTrait;

    public function toDto(?string $ip = null): H5LoginInput
    {
        $payload = $this->validated();
        $payload['ip'] = $ip;

        return Mapper::map($payload, new H5LoginDto());
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
