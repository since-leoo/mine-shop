<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller\V1\Auth;

use App\Application\Api\Auth\AppApiAuthCommandService;
use App\Application\Api\Auth\AppApiAuthQueryService;
use App\Interface\Api\Middleware\ApiSignatureMiddleware;
use App\Interface\Api\Request\V1\ForgotPasswordRequest;
use App\Interface\Api\Request\V1\RegisterRequest;
use App\Interface\Api\Request\V1\SendVerificationCodeRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: '/api/v1/auth')]
#[Middleware(ApiSignatureMiddleware::class)]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AppApiAuthCommandService $authService,
        private readonly AppApiAuthQueryService $authQueryService,
    ) {}

    #[GetMapping(path: 'register/protocols')]
    public function registerProtocols(): Result
    {
        return $this->success($this->authQueryService->registerProtocols());
    }

    #[PostMapping(path: 'captcha')]
    public function captcha(SendVerificationCodeRequest $request): Result
    {
        $result = $this->authService->sendVerificationCode($request->toDto());

        return $this->success($result, '验证码发送成功');
    }

    #[PostMapping(path: 'register')]
    public function register(RegisterRequest $request): Result
    {
        $result = $this->authService->register($request->toDto());

        return $this->success($result, '注册成功');
    }

    #[PostMapping(path: 'forgotPassword')]
    public function forgotPassword(ForgotPasswordRequest $request): Result
    {
        $this->authService->forgotPassword($request->toDto());

        return $this->success([], '密码重置成功');
    }
}
