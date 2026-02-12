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

namespace App\Application\Api\Member;

use App\Domain\Auth\Service\DomainTokenService;
use App\Domain\Member\Api\Command\DomainApiMemberAuthCommandService;
use App\Domain\Member\Contract\ProfileAuthorizeInput;
use App\Domain\Member\Contract\ProfileUpdateInput;

/**
 * 小程序认证应用服务：编排登录、绑定手机号、授权头像昵称等流程.
 */
final class AppApiMemberAuthCommandService
{
    public function __construct(
        private readonly DomainApiMemberAuthCommandService $authCommandService,
        private readonly DomainTokenService $tokenService,
    ) {}

    /**
     * 小程序登录.
     *
     * @return array{token: string, refresh_token: string, expires_in: int, member: array<string, mixed>}
     */
    public function miniProgramLogin(string $code, ?string $encryptedData = null, ?string $iv = null, ?string $ip = null, ?string $openid = null): array
    {
        $member = $this->authCommandService->miniProgramLogin($code, $encryptedData, $iv, $ip, $openid);
        $tokenService = $this->tokenService->using('api');

        return [
            'token' => $tokenService->buildAccessToken('member:' . $member->getId()),
            'refresh_token' => $tokenService->buildRefreshToken('member:' . $member->getId()),
            'expires_in' => $tokenService->getTtl(),
            'member' => [
                'id' => $member->getId(),
                'nickname' => $member->getNickname(),
                'avatar' => $member->getAvatar(),
                'gender' => $member->getGender(),
                'source' => $member->getSource(),
                'openid' => $member->getOpenid(),
            ],
        ];
    }

    /**
     * 绑定手机号.
     *
     * @return array{phone_number: string, pure_phone_number: string, country_code: null|string}
     */
    public function bindPhoneNumber(int $memberId, string $code): array
    {
        return $this->authCommandService->bindPhoneNumber($memberId, $code);
    }

    /**
     * 授权头像昵称.
     */
    public function authorizeProfile(int $memberId, ProfileAuthorizeInput $input): void
    {
        $this->authCommandService->authorizeProfile($memberId, $input);
    }

    /**
     * 修改个人资料.
     */
    public function updateProfile(int $memberId, ProfileUpdateInput $input): void
    {
        $this->authCommandService->updateProfile($memberId, $input);
    }
}
