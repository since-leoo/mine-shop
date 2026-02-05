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
use App\Infrastructure\Abstract\IService;

/**
 * 会员标签服务.
 */
final class MemberTagService extends IService
{
    public function __construct(public readonly MemberTagRepository $repository) {}

    /**
     * @param MemberTagEntity $entity
     * @return void
     */
    public function create(MemberTagEntity $entity): void
    {
        $this->repository->save($entity);
    }

    /**
     * @param MemberTagEntity $entity
     * @return void
     */
    public function update(MemberTagEntity $entity): void
    {
        $this->repository->updateEntity($entity);
    }

    public function delete(int $id): bool
    {
        return $this->repository->deleteById($id) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this->repository->allActive();
    }

}
