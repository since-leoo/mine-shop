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

use App\Domain\Permission\Repository\LeaderRepository;

final class LeaderCommandService
{
    public function __construct(public readonly LeaderRepository $repository) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->repository->create($payload);
    }

    /**
     * @param \App\Domain\Permission\Contract\Leader\LeaderDeleteInput $input
     */
    public function delete(\App\Domain\Permission\Contract\Leader\LeaderDeleteInput $input): void
    {
        $this->repository->deleteByDoubleKey($input->getDeptId(), $input->getUserIds());
    }
}
