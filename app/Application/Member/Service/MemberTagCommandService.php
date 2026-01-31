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
     * @return array<string, mixed>
     */
    public function create(MemberTagEntity $entity): array
    {
        return $this->memberTagService->create($entity);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(MemberTagEntity $entity): array
    {
        return $this->memberTagService->update($entity);
    }

    public function delete(int $id): bool
    {
        return $this->memberTagService->delete($id);
    }
}
