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

use App\Domain\Member\Service\DomainMemberReferralService;

/**
 * 小程序端分销/邀请查询服务.
 */
final class AppApiMemberReferralQueryService
{
    public function __construct(
        private readonly DomainMemberReferralService $referralService,
    ) {}

    /**
     * 获取当前会员的邀请码.
     */
    public function inviteCode(int $memberId): ?string
    {
        return $this->referralService->getInviteCode($memberId);
    }

    /**
     * 查询我的下级列表（分页）.
     */
    public function myReferrals(int $memberId, int $page = 1, int $pageSize = 10): array
    {
        return $this->referralService->referrals($memberId, $page, $pageSize);
    }

    /**
     * 查询我的下级数量.
     */
    public function myReferralCount(int $memberId): int
    {
        return $this->referralService->referralCount($memberId);
    }

    /**
     * 生成邀请小程序码.
     *
     * @return array{path?: string, name?: string, msg: string}
     */
    public function generateInviteQrCode(int $memberId, string $page = 'pages/home/home'): array
    {
        return $this->referralService->generateInviteQrCode($memberId, $page);
    }
}
