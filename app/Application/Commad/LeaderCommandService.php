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

use App\Domain\Permission\Contract\Leader\LeaderCreateInput;
use App\Domain\Permission\Contract\Leader\LeaderDeleteInput;
use App\Domain\Permission\Service\LeaderService;

final class LeaderCommandService
{
    public function __construct(
        private readonly LeaderService $leaderService
    ) {}

    public function create(LeaderCreateInput $input): mixed
    {
        return $this->leaderService->create($input);
    }

    public function delete(LeaderDeleteInput $input): void
    {
        $this->leaderService->delete($input->getDeptId(), $input->getUserIds());
    }
}
