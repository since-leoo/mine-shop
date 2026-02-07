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

namespace App\Application\Admin\Organization;

use App\Domain\Organization\Contract\Leader\LeaderCreateInput;
use App\Domain\Organization\Contract\Leader\LeaderDeleteInput;
use App\Domain\Organization\Service\DomainLeaderService;

final class AppLeaderCommandService
{
    public function __construct(
        private readonly DomainLeaderService $leaderService
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
