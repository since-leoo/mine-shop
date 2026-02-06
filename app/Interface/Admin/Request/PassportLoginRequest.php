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

namespace App\Interface\Admin\Request;

use App\Domain\Auth\Enum\Type;
use App\Interface\Admin\DTO\PassportLoginDto;
use App\Interface\Common\Request\BaseRequest;
use App\Interface\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Collection\Arr;
use Hyperf\DTO\Mapper;
use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use Mine\Support\Request\ClientIpRequestTrait;
use Mine\Support\Request\ClientOsTrait;

#[Schema(title: '登录请求', description: '登录请求参数', properties: [
    new Property('username', description: '用户名', type: 'string'),
    new Property('password', description: '密码', type: 'string'),
])]
class PassportLoginRequest extends BaseRequest
{
    use ClientIpRequestTrait;
    use ClientOsTrait;
    use NoAuthorizeTrait;

    public function loginRules(): array
    {
        return [
            'username' => 'required|string|exists:user,username',
            'password' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => trans('user.username'),
            'password' => trans('user.password'),
        ];
    }

    public function ip(): string
    {
        return Arr::first($this->getClientIps(), static fn ($ip) => $ip, '0.0.0.0');
    }

    public function toDto(): PassportLoginDto
    {
        $dto = Mapper::map($this->validated(), new PassportLoginDto());
        $dto->userType = Type::SYSTEM;

        return $dto->withClient(
            $this->ip(),
            $this->header('User-Agent') ?: 'unknown',
            $this->os()
        );
    }
}
