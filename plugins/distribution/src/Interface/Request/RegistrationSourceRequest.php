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

namespace Plugin\Distribution\Interface\Request;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Plugin\Distribution\Domain\ValueObject\RegistrationSourceVo;

/** 插件独立验证扩展输入；不要求宿主登录/注册 Request 或 DTO 声明业务字段。 */
final class RegistrationSourceRequest
{
    public function __construct(private readonly RequestInterface $request, private readonly ValidatorFactoryInterface $validator) {}

    public function source(): RegistrationSourceVo
    {
        $data = ['invite_code' => $this->request->input('invite_code'), 'share_token' => $this->request->input('share_token')];
        $validator = $this->validator->make($data, [
            'invite_code' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{4,32}$/D'],
            'share_token' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{6,32}$/D'],
        ], [], ['invite_code' => '邀请码', 'share_token' => '邀请分享标识']);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return new RegistrationSourceVo($data['invite_code'] ?: null, $data['share_token'] ?: null);
    }
}
