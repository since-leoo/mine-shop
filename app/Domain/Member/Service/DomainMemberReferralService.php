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

use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Service\Wechat\MiniAppAuthService;

/**
 * 会员分销/邀请领域服务.
 */
final class DomainMemberReferralService extends IService
{
    public function __construct(
        private readonly MemberRepository $repository,
        private readonly MiniAppAuthService $miniAppAuthService,
    ) {}

    /**
     * 查询会员的直接下级列表（分页）.
     */
    public function referrals(int $memberId, int $page = 1, int $pageSize = 10): array
    {
        return $this->repository->referralPage($memberId, $page, $pageSize);
    }

    /**
     * 查询会员的直接下级数量.
     */
    public function referralCount(int $memberId): int
    {
        return $this->repository->referralCount($memberId);
    }

    /**
     * 获取会员的邀请码.
     */
    public function getInviteCode(int $memberId): ?string
    {
        /** @var null|Member $member */
        $member = $this->repository->findById($memberId);
        return $member?->invite_code;
    }

    /**
     * 生成会员邀请小程序码.
     *
     * @return array{path?: string, name?: string, msg: string}
     */
    public function generateInviteQrCode(int $memberId, string $page = 'pages/home/home'): array
    {
        $inviteCode = $this->getInviteCode($memberId);
        if (! $inviteCode) {
            return ['msg' => '邀请码不存在'];
        }

        return $this->miniAppAuthService->getWxaCode($page, 'invite_code=' . $inviteCode);
    }
}
