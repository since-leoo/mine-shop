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
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberTagRepository;

/**
 * 会员领域服务.
 */
final class MemberService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MemberTagRepository $memberTagRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->memberRepository->page($filters, $page, $pageSize);
    }

    /**
     * @return array<string, int>
     */
    public function stats(array $filters): array
    {
        return $this->memberRepository->stats($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        return $this->memberRepository->overview($filters);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->memberRepository->detail($id);
    }

    public function create(MemberEntity $entity): void
    {
        if (! $entity->getSource()) {
            $entity->setSource('admin');
        }

        $member = $this->memberRepository->save($entity);

        $entity->setId($member->id);

        if ($entity->getTagIds() !== []) {
            $this->applyTags($entity->getId(), $entity->getTagIds());
        }
    }

    /**
     * 更新会员档案.
     */
    public function update(MemberEntity $entity): void
    {
        $this->ensureMemberExists($entity->getId());
        $this->memberRepository->updateEntity($entity);
    }

    /**
     * 更新会员状态.
     */
    public function updateStatus(MemberEntity $entity): void
    {
        $memberId = $entity->getId();
        $this->ensureMemberExists($memberId);

        if (! $entity->getStatus()) {
            throw new \InvalidArgumentException('会员状态不能为空');
        }

        $this->memberRepository->updateEntity($entity);
    }

    /**
     * 同步会员标签.
     */
    public function syncTags(MemberEntity $entity): void
    {
        $memberId = $entity->getId();
        $this->ensureMemberExists($memberId);
        $this->applyTags($memberId, $entity->getTagIds());
    }

    private function ensureMemberExists(int $memberId): void
    {
        if (! $this->memberRepository->existsById($memberId)) {
            throw new \RuntimeException('会员不存在');
        }
    }

    private function applyTags(int $memberId, array $tagIds): void
    {
        $available = $tagIds === [] ? [] : $this->memberTagRepository
            ->getQuery()
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->toArray();

        $this->memberRepository->syncTags($memberId, $available);
    }
}
