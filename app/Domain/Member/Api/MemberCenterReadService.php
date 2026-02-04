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

namespace App\Domain\Member\Api;

use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class MemberCenterReadService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(int $memberId): array
    {
        $member = $this->memberRepository->detail($memberId);
        if ($member === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        return [
            'id' => $member['id'],
            'avatarUrl' => $member['avatar'] ?? null,
            'nickName' => $member['nickname'] ?? '',
            'phoneNumber' => $member['phone'] ?? '',
            'gender' => $member['gender'] ?? 'unknown',
            'levelName' => $member['level_definition']['name'] ?? null,
            'level' => $member['level'] ?? null,
            'balance' => (float) ($member['wallet']['balance'] ?? 0),
            'points' => (int) ($member['points_wallet']['balance'] ?? 0),
        ];
    }
}
