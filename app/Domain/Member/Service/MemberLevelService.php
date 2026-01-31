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

use App\Domain\Member\Entity\MemberLevelEntity;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Infrastructure\Model\Member\MemberLevel;

final class MemberLevelService
{
    public function __construct(private readonly MemberLevelRepository $repository) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->repository->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?MemberLevel
    {
        /** @var null|MemberLevel $level */
        return $this->repository->findById($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function create(MemberLevelEntity $entity): array
    {
        $level = $this->repository->save($entity);
        return $level->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function update(MemberLevelEntity $entity): array
    {
        $this->ensureExists($entity->getId());

        $this->repository->updateEntity($entity);
        /** @var MemberLevel $fresh */
        $fresh = $this->repository->findById($entity->getId());
        return $fresh->toArray();
    }

    public function delete(int $id): bool
    {
        $this->ensureExists($id);
        return $this->repository->deleteById($id) > 0;
    }

    private function ensureExists(int $id): void
    {
        if (! $this->repository->existsById($id)) {
            throw new \RuntimeException('会员等级不存在');
        }
    }
}
