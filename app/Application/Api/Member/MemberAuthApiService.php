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
use App\Domain\Member\Service\MemberAuthService;
use App\Domain\Member\Service\MemberService;
use Plugin\Wechat\Interfaces\MiniAppInterface;

final class MemberAuthApiService
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly MemberAuthService $authService,
        private readonly TokenService $tokenService,
        private readonly MiniAppInterface $miniApp,
    ) {}

    /**
     * 小程序登录.
     */
    public function miniProgramLogin(string $code, ?string $encryptedData = null, ?string $iv = null, ?string $ip = null, ?string $openid = null): array
    {
        if (! empty($encryptedData) && ! empty($iv)) {
            $payload = $this->miniApp->performSilentLogin($code, $encryptedData, $iv);
        } else {
            $payload = $this->miniApp->silentAuthorize($code);
        }

        $openid = $openid ?: (string) ($payload['openid'] ?? '');

        if (empty($openid)) {
            throw new \InvalidArgumentException('授权失败');
        }

        // 登录
        $member = $this->authService->miniProgramLogin($openid, $ip, $payload);
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
     * @param int $memberId
     * @param string $code
     * @return void
     */
    public function bindPhoneNumber(int $memberId, string $code): void
    {
        $memberEntity = $this->memberService->getEntity($memberId);

        $payload = $this->miniApp->getPhoneNumber($code);

        // 兼容旧版
        $phoneInfo = $payload['phone_info'] ?? $payload;
        $phoneNumber = (string) ($phoneInfo['phoneNumber'] ?? $phoneInfo['purePhoneNumber'] ?? '');

        if (trim($phoneNumber) === '') {
            throw new \InvalidArgumentException('获取手机号失败');
        }

        $memberEntity->bindPhone($phoneNumber);

        $this->memberService->update($memberEntity);
    }

    /**
     * @param array{nickname?: string, avatar_url?: string, gender?: null|int} $payload
     */
    public function authorizeProfile(int $memberId, array $payload): void
    {
        $memberEntity = $this->memberService->getEntity($memberId);

        $memberEntity->setAvatar($payload['avatar_url'] ?? $memberEntity->getAvatar());
        $memberEntity->setNickname($payload['nickname'] ?? $memberEntity->getNickname());

        $this->memberService->update($memberEntity);
    }
}
