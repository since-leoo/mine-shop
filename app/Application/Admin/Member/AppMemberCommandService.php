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

namespace App\Application\Admin\Member;

use App\Domain\Member\Contract\MemberInput;
use App\Domain\Member\Service\DomainMemberService;
use Hyperf\DbConnection\Db;

/**
 * 会员写应用服务.
 */
final class AppMemberCommandService
{
    public function __construct(
        private readonly DomainMemberService $memberService
    ) {}

    /**
     * 创建会员.
     *
     * @return array<string, mixed>
     */
    public function create(MemberInput $input): array
    {
        // 事务管理
        $member = Db::transaction(fn () => $this->memberService->create($input));

        return $member->toArray();
    }

    /**
     * 更新会员.
     *
     * @return array<string, mixed>
     */
    public function update(MemberInput $input): array
    {
        // 事务管理
        $member = Db::transaction(fn () => $this->memberService->update($input));

        return $member->toArray();
    }

    /**
     * 更新会员状态.
     */
    public function updateStatus(int $memberId, string $status): void
    {
        Db::transaction(fn () => $this->memberService->updateStatus($memberId, $status));
    }

    /**
     * 同步会员标签.
     *
     * @param int[] $tagIds
     */
    public function syncTags(int $memberId, array $tagIds): void
    {
        Db::transaction(fn () => $this->memberService->syncTags($memberId, $tagIds));
    }
}
