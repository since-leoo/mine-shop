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

use App\Application\Member\Service\MemberCommandService;
use App\Interface\Api\Request\V1\LoginRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController(prefix: '/api/v1/login')]
final class LoginController extends AbstractController
{
    public function __construct(private readonly MemberCommandService $commandService) {}

    public function miniApp(LoginRequest $request): Result
    {
        $payload = $request->validated();

        $result = $this->commandService->miniProgramLogin(
            $payload['code'],
            $payload['encrypted_data'],
            $payload['iv'],
            ip()
        );

        return $this->success($result, '登录成功');
    }
}
