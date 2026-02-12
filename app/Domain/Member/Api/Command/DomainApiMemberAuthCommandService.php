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

namespace App\Domain\Member\Api\Command;

use App\Domain\Member\Contract\ProfileAuthorizeInput;
use App\Domain\Member\Contract\ProfileUpdateInput;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Mapper\MemberMapper;
use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
use Carbon\Carbon;
use Plugin\Wechat\Interfaces\MiniAppInterface;

/**
 * 面向 API 场景的会员认证写领域服务.
 *
 * 包含小程序登录、手机号绑定、头像昵称授权等认证相关业务.
 * MemberAuthService 的所有方法均为 API 专属，已全部迁移至此.
 */
final class DomainApiMemberAuthCommandService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MiniAppInterface $miniApp,
    ) {}

    /**
     * 小程序静默登录.
     */
    public function miniProgramLogin(
        string $code,
        ?string $encryptedData = null,
        ?string $iv = null,
        ?string $ip = null,
        ?string $manualOpenid = null
    ): MemberEntity {
        if (! empty($encryptedData) && ! empty($iv)) {
            $payload = $this->miniApp->performSilentLogin($code, $encryptedData, $iv);
        } else {
            $payload = $this->miniApp->silentAuthorize($code);
        }

        $openid = $manualOpenid ?: (string) ($payload['openid'] ?? '');

        if (empty($openid)) {
            throw new \InvalidArgumentException('授权失败');
        }

        $memberEntity = $this->memberRepository->findByOpenid($openid);

        if (! $memberEntity) {
            $memberEntity = MemberMapper::fromMiniProfile($payload);
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $model = $this->memberRepository->save($memberEntity);
            $memberEntity->setId($model->id);
        } else {
            $memberEntity->setUnionid($payload['unionid'] ?? $memberEntity->getUnionid());
            $memberEntity->setSource('mini_program');
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip ?? '');
            $this->memberRepository->updateEntity($memberEntity);
        }

        return $memberEntity;
    }

    /**
     * 绑定手机号.
     *
     * @return array{phone_number: string, pure_phone_number: string, country_code: null|string}
     */
    public function bindPhoneNumber(int $memberId, string $code): array
    {
        $memberEntity = $this->getEntity($memberId);

        $payload = $this->miniApp->getPhoneNumber($code);
        $phoneInfo = $payload['phone_info'] ?? $payload;
        $phoneNumber = (string) ($phoneInfo['phoneNumber'] ?? $phoneInfo['purePhoneNumber'] ?? '');

        if (trim($phoneNumber) === '') {
            throw new \InvalidArgumentException('获取手机号失败');
        }

        $memberEntity->bindPhone($phoneNumber);
        $this->memberRepository->updateEntity($memberEntity);

        return [
            'phone_number' => $phoneNumber,
            'pure_phone_number' => (string) ($phoneInfo['purePhoneNumber'] ?? $phoneNumber),
            'country_code' => $phoneInfo['countryCode'] ?? null,
        ];
    }

    /**
     * 授权头像昵称.
     */
    public function authorizeProfile(int $memberId, ProfileAuthorizeInput $input): void
    {
        $memberEntity = $this->getEntity($memberId);
        $memberEntity->authorizeProfile($input);
        $this->memberRepository->updateEntity($memberEntity);
    }

    /**
     * 修改个人资料.
     */
    public function updateProfile(int $memberId, ProfileUpdateInput $input): void
    {
        $memberEntity = $this->getEntity($memberId);
        $memberEntity->updateProfile($input);
        $this->memberRepository->updateEntity($memberEntity);
    }

    /**
     * 获取会员实体.
     */
    private function getEntity(int $memberId): MemberEntity
    {
        $model = $this->memberRepository->findById($memberId);

        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, "会员不存在: ID={$memberId}");
        }

        return MemberMapper::fromModel($model);
    }
}
