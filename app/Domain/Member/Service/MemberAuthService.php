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

namespace App\Domain\Member\Service;

use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Mapper\MemberMapper;
use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Abstract\IService;
use Carbon\Carbon;

/**
 * 会员领域服务.
 */
final class MemberAuthService extends IService
{

    public function __construct(
        private readonly MemberRepository $memberRepository,
    ) {}

    /**
     * 小程序登录.
     */
    public function miniProgramLogin(string $openid, string $ip, array $payload): MemberEntity
    {
        $memberEntity = $this->memberRepository->findByOpenid($openid);

        if (! $memberEntity) {
            $memberEntity = MemberMapper::fromMiniProfile($payload);
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip);
            $model = $this->memberRepository->save($memberEntity);
            $memberEntity->setId($model->id);
        } else {
            $memberEntity->setUnionid($payload['unionid'] ?? $memberEntity->getUnionid());
            $memberEntity->setNickname($payload['nickname'] ?? $memberEntity->getNickname());
            $memberEntity->setAvatar($payload['avatarUrl'] ?? $memberEntity->getAvatar());
            $memberEntity->setGender($payload['gender'] ?? $memberEntity->getGender());
            $memberEntity->setSource('mini_program');
            $memberEntity->setLastLoginAt(Carbon::now());
            $memberEntity->setLastLoginIp($ip);
            $this->memberRepository->updateEntity($memberEntity);
        }

        return $memberEntity;
    }
}
