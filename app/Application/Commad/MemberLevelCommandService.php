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

namespace App\Application\Commad;

use App\Domain\Member\Entity\MemberLevelEntity;
use App\Domain\Member\Service\MemberLevelService;

final class MemberLevelCommandService
{
    public function __construct(private readonly MemberLevelService $memberLevelService) {}

    /**
     * @return array<string, mixed>
     */
    public function create(MemberLevelEntity $entity): array
    {
        return $this->memberLevelService->create($entity);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(MemberLevelEntity $entity): array
    {
        return $this->memberLevelService->update($entity);
    }

    public function delete(int $id): bool
    {
        return $this->memberLevelService->delete($id);
    }
}
