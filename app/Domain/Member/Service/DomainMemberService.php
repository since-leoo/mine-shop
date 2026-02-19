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
use App\Domain\Member\Repository\MemberTagRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\Member;
use App\Interface\Common\ResultCode;

/**
 * 会员领域服务.
 *
 * 负责会员档案的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainMemberService extends IService
{
    public function __construct(
        protected readonly MemberRepository $repository,
        private readonly MemberTagRepository $memberTagRepository,
    ) {}

    /**
     * 创建会员.
     *
     * @param MemberEntity $entity 会员实体
     * @return Member 创建的模型
     */
    public function create(MemberEntity $entity): Member
    {
        $member = $this->repository->save($entity);

        // 同步标签
        if ($entity->getTagIds() !== []) {
            $this->applyTags($member->id, $entity->getTagIds());
        }

        return $member;
    }

    /**
     * 更新会员档案.
     *
     * @param MemberEntity $entity 更新后的实体
     * @return Member 更新后的模型
     */
    public function update(MemberEntity $entity): Member
    {
        $model = $this->repository->findById($entity->getId());
        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $this->repository->updateEntity($entity);

        return $model->refresh();
    }

    /**
     * 更新会员状态.
     *
     * @param int $memberId 会员 ID
     * @param string $status 新状态
     * @return Member 更新后的模型
     */
    public function updateStatus(int $memberId, string $status): Member
    {
        $entity = $this->getEntity($memberId);
        $entity->updateStatus($status);
        $this->repository->updateEntity($entity);

        return $this->repository->findById($memberId);
    }

    /**
     * 同步会员标签.
     *
     * @param int $memberId 会员 ID
     * @param array $tagIds 标签 ID 数组
     */
    public function syncTags(int $memberId, array $tagIds): void
    {
        $this->ensureMemberExists($memberId);
        $this->applyTags($memberId, $tagIds);
    }

    /**
     * 获取会员实体.
     *
     * @param int $memberId 会员 ID
     * @return MemberEntity 会员实体
     * @throws BusinessException 会员不存在时抛出
     */
    public function getEntity(int $memberId): MemberEntity
    {
        /** @var null|Member $model */
        $model = $this->repository->findById($memberId);

        if (! $model) {
            throw new BusinessException(ResultCode::NOT_FOUND, "会员不存在: ID={$memberId}");
        }

        return MemberMapper::fromModel($model);
    }

    /**
     * 获取会员统计数据.
     *
     * @return array<string, int>
     */
    public function stats(array $filters): array
    {
        return $this->repository->stats($filters);
    }

    /**
     * 获取会员概览数据.
     *
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        return $this->repository->overview($filters);
    }

    /**
     * 获取会员详情.
     *
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->repository->detail($id);
    }

    /**
     * 确保会员存在.
     *
     * @throws BusinessException 会员不存在时抛出
     */
    private function ensureMemberExists(int $memberId): void
    {
        if (! $this->repository->existsById($memberId)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }
    }

    /**
     * 应用标签到会员.
     */
    private function applyTags(int $memberId, array $tagIds): void
    {
        $available = $tagIds === [] ? [] : $this->memberTagRepository
            ->getQuery()
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->toArray();

        $this->repository->syncTags($memberId, $available);
    }
}
