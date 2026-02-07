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

namespace App\Interface\Api\Transformer;

/**
 * 会员 profile 数据转换器：直接输出后端 snake_case 字段，小程序端自行适配.
 */
final class MemberProfileTransformer
{
    /**
     * @param array<string, mixed> $member
     * @return array<string, mixed>
     */
    public function transform(array $member): array
    {
        $nickname = $member['nickname'] ?? '';
        $avatar = $member['avatar'] ?? null;

        return [
            'id' => $member['id'],
            'avatar' => $avatar,
            'nickname' => $nickname,
            'phone' => $member['phone'] ?? '',
            'gender' => $member['gender'] ?? 'unknown',
            'level_name' => $member['level_definition']['name'] ?? null,
            'level' => $member['level'] ?? null,
            'balance' => (int) ($member['wallet']['balance'] ?? 0),
            'points' => (int) ($member['points_wallet']['balance'] ?? 0),
            'authorized_profile' => ($nickname !== '' && $nickname !== '微信用户' && $avatar !== null && $avatar !== ''),
        ];
    }
}
