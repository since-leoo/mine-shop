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

namespace App\Interface\Admin\Controller;

use App\Application\Admin\Permission\AppAuthCommandService;
use App\Interface\Admin\Request\PassportLoginRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\RefreshTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Jwt\Traits\RequestScopedTokenTrait;

#[Controller(prefix: '/admin/passport')]
final class PassportController extends AbstractController
{
    use RequestScopedTokenTrait;

    public function __construct(
        private readonly AppAuthCommandService $authCommandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[PostMapping(path: 'login')]
    public function login(PassportLoginRequest $request): Result
    {
        return $this->success($this->authCommandService->login($request->toDto())->toArray());
    }

    #[PostMapping(path: 'logout')]
    #[Middleware(AccessTokenMiddleware::class)]
    public function logout(): Result
    {
        $this->authCommandService->logout($this->getToken());
        return $this->success();
    }

    #[GetMapping(path: 'getInfo')]
    #[Middleware(AccessTokenMiddleware::class)]
    public function getInfo(): Result
    {
        return $this->success(
            Arr::only(
                $this->currentUser->user()?->toArray() ?: [],
                ['username', 'nickname', 'avatar', 'signed', 'backend_setting', 'phone', 'email']
            )
        );
    }

    #[PostMapping(path: 'refresh')]
    #[Middleware(RefreshTokenMiddleware::class)]
    public function refresh(): Result
    {
        return $this->success($this->authCommandService->refresh($this->getToken())->toArray());
    }
}
