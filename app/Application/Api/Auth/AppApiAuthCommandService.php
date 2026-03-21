<?php

declare(strict_types=1);

namespace App\Application\Api\Auth;

use App\Domain\Auth\Service\DomainTokenService;
use App\Domain\Member\Api\Command\DomainApiMemberPasswordAuthService;
use App\Domain\Member\Contract\ForgotPasswordInput;
use App\Domain\Member\Contract\H5LoginInput;
use App\Domain\Member\Contract\RegisterInput;
use App\Domain\Member\Contract\VerificationCodeSendInput;
use App\Domain\Member\Entity\MemberEntity;

final class AppApiAuthCommandService
{
    public function __construct(
        private readonly DomainApiMemberPasswordAuthService $passwordAuthService,
        private readonly DomainTokenService $tokenService,
    ) {}

    /**
     * @return array{token: string, refresh_token: string, expires_in: int, member: array<string, mixed>}
     */
    public function loginByPassword(H5LoginInput $input): array
    {
        return $this->issueTokenPayload($this->passwordAuthService->loginByPassword($input));
    }

    /**
     * @return array{phone: string, scene: string, code?: string}
     */
    public function sendVerificationCode(VerificationCodeSendInput $input): array
    {
        return $this->passwordAuthService->sendVerificationCode($input);
    }

    /**
     * @return array{token: string, refresh_token: string, expires_in: int, member: array<string, mixed>}
     */
    public function register(RegisterInput $input): array
    {
        return $this->issueTokenPayload($this->passwordAuthService->register($input));
    }

    public function forgotPassword(ForgotPasswordInput $input): void
    {
        $this->passwordAuthService->resetPassword($input);
    }

    /**
     * @return array{token: string, refresh_token: string, expires_in: int, member: array<string, mixed>}
     */
    private function issueTokenPayload(MemberEntity $member): array
    {
        $tokenService = $this->tokenService->using('api');

        return [
            'token' => $tokenService->buildAccessToken('member:' . $member->getId()),
            'refresh_token' => $tokenService->buildRefreshToken('member:' . $member->getId()),
            'expires_in' => $tokenService->getTtl(),
            'member' => [
                'id' => $member->getId(),
                'phone' => $member->getPhone(),
                'nickname' => $member->getNickname(),
                'avatar' => $member->getAvatar(),
                'source' => $member->getSource(),
            ],
        ];
    }
}