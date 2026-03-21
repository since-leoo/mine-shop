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

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Auth\AppApiAuthCommandService;
use App\Application\Api\Member\AppApiMemberAuthCommandService;
use App\Interface\Api\Request\V1\H5PasswordLoginRequest;
use App\Interface\Api\Request\V1\LoginRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController(prefix: '/api/v1/login')]
final class LoginController extends AbstractController
{
    public function __construct(
        private readonly AppApiMemberAuthCommandService $commandService,
        private readonly AppApiAuthCommandService $authService,
    ) {}

    public function miniApp(LoginRequest $request): Result
    {
        $payload = $request->validated();

        $result = $this->commandService->miniProgramLogin(
            $payload['code'],
            $payload['encrypted_data'] ?? null,
            $payload['iv'] ?? null,
            ip(),
            $payload['openid'] ?? null
        );

        return $this->success($result, '授权登录失败');
    }

    public function h5Password(H5PasswordLoginRequest $request): Result
    {
        $result = $this->authService->loginByPassword($request->toDto(ip()));

        return $this->success($result, '登录失败');
    }
}
