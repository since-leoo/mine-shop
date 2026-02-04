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

use App\Domain\Auth\Service\TokenService;
use App\Domain\Member\Service\MemberService;

final class MemberAuthApiService
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly TokenService $tokenService
    ) {}

    /**
     * 小程序登录.
     */
    public function miniProgramLogin(string $code, ?string $encryptedData = null, ?string $iv = null, ?string $ip = null, ?string $openid = null): array
    {
        $member = $this->memberService->miniProgramLogin($code, $encryptedData, $iv, $ip, $openid);

        $tokenService = $this->tokenService->using('api');
        $identifier = 'member:' . $member->getId();

        return [
            'token' => $tokenService->buildAccessToken($identifier),
            'refresh_token' => $tokenService->buildRefreshToken($identifier),
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
     * @return array{phoneNumber: string, purePhoneNumber: string, countryCode: null|string}
     */
    public function bindPhoneNumber(int $memberId, string $code): array
    {
        $memberEntity = $this->memberService->getInfoEntity($memberId);

        return $this->memberService->bindPhoneNumber($memberEntity, $code);
    }

    /**
     * @param array{nickname?: string, avatar_url?: string, gender?: null|int} $payload
     */
    public function authorizeProfile(int $memberId, array $payload): void
    {
        $memberEntity = $this->memberService->getInfoEntity($memberId);

        $memberEntity->setAvatar($payload['avatar_url'] ?? $memberEntity->getAvatar());
        $memberEntity->setNickname($payload['nickname'] ?? $memberEntity->getNickname());

        $this->memberService->update($memberEntity);
    }
}
