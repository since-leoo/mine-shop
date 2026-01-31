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

use App\Domain\Member\Entity\MemberTagEntity;
use App\Domain\Member\Repository\MemberTagRepository;
use App\Infrastructure\Model\Member\MemberTag;
use Hyperf\Collection\Collection;

/**
 * 会员标签服务.
 */
final class MemberTagService
{
    public function __construct(private readonly MemberTagRepository $repository) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        /** @var Collection<int, MemberTag> $collection */
        $collection = $this->repository->list($filters);
        return $collection->map(static fn (MemberTag $tag) => $tag->toArray())->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(MemberTagEntity $entity): array
    {
        $this->ensureUniqueName($entity->getName() ?? '');
        $tag = $this->repository->save($entity);
        return $tag->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function update(MemberTagEntity $entity): array
    {
        $this->ensureTagExists($entity->getId());
        if ($entity->getName()) {
            $this->ensureUniqueName($entity->getName(), $entity->getId());
        }
        $this->repository->updateEntity($entity);
        /** @var null|MemberTag $tag */
        $tag = $this->repository->findById($entity->getId());
        if (! $tag) {
            throw new \RuntimeException('标签不存在');
        }
        return $tag->toArray();
    }

    public function delete(int $id): bool
    {
        $this->ensureTagExists($id);
        return $this->repository->deleteById($id) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this->repository->allActive();
    }

    private function ensureTagExists(int $id): void
    {
        if (! $this->repository->existsById($id)) {
            throw new \RuntimeException('标签不存在');
        }
    }

    private function ensureUniqueName(string $name, ?int $exceptId = null): void
    {
        if ($name === '') {
            throw new \RuntimeException('标签名称不能为空');
        }
        if ($this->repository->existsByName($name, $exceptId)) {
            throw new \RuntimeException('标签名称已存在');
        }
    }
}
