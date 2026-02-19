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
use App\Domain\Member\Mapper\MemberMapper;
use App\Domain\Member\Service\DomainMemberService;
use Hyperf\DbConnection\Db;

/**
 * 会员应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppMemberCommandService
{
    public function __construct(
        private readonly DomainMemberService $memberService
    ) {}

    /**
     * 创建会员.
     *
     * @param MemberInput $input 会员输入 DTO
     * @return array<string, mixed>
     */
    public function create(MemberInput $input): array
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = MemberMapper::fromDto($input);
        $member = Db::transaction(fn () => $this->memberService->create($entity));
        return $member->toArray();
    }

    /**
     * 更新会员.
     *
     * @param MemberInput $input 会员输入 DTO
     * @return array<string, mixed>
     */
    public function update(MemberInput $input): array
    {
        // 从数据库获取实体并更新
        $entity = $this->memberService->getEntity($input->getId());
        $entity->update($input);
        $member = Db::transaction(fn () => $this->memberService->update($entity));
        return $member->toArray();
    }

    /**
     * 更新会员状态.
     *
     * @param int $memberId 会员 ID
     * @param string $status 新状态
     */
    public function updateStatus(int $memberId, string $status): void
    {
        Db::transaction(fn () => $this->memberService->updateStatus($memberId, $status));
    }

    /**
     * 同步会员标签.
     *
     * @param int $memberId 会员 ID
     * @param int[] $tagIds 标签 ID 数组
     */
    public function syncTags(int $memberId, array $tagIds): void
    {
        Db::transaction(fn () => $this->memberService->syncTags($memberId, $tagIds));
    }
}
