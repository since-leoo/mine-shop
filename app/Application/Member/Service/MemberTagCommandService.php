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

namespace App\Application\Member\Service;

use App\Domain\Member\Entity\MemberTagEntity;
use App\Domain\Member\Service\MemberTagService;

final class MemberTagCommandService
{
    public function __construct(private readonly MemberTagService $memberTagService) {}

    /**
     * @param MemberTagEntity $entity
     * @return void
     */
    public function create(MemberTagEntity $entity): void
    {
        $this->memberTagService->create($entity);
    }

    /**
     * @param MemberTagEntity $entity
     * @return void
     */
    public function update(MemberTagEntity $entity): void
    {
        $this->memberTagService->update($entity);
    }

    public function delete(int $id): bool
    {
        return $this->memberTagService->delete($id);
    }
}
