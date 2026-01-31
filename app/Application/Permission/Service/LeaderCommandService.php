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

namespace App\Application\Permission\Service;

use App\Domain\Permission\Repository\LeaderRepository;

final class LeaderCommandService
{
    public function __construct(private readonly LeaderRepository $repository) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->repository->create($payload);
    }

    /**
     * @param array<int|string> $userIds
     */
    public function delete(int $deptId, array $userIds): void
    {
        $this->repository->deleteByDoubleKey($deptId, $userIds);
    }
}
