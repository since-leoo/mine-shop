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

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Contract\Leader\LeaderCreateInput;
use App\Domain\Permission\Repository\LeaderRepository;
use App\Infrastructure\Abstract\IService;

final class DomainLeaderService extends IService
{
    public function __construct(
        private readonly LeaderRepository $repository
    ) {}

    /**
     * 创建领导.
     */
    public function create(LeaderCreateInput $input): mixed
    {
        // 使用 DTO 的 toArray() 方法获取数据
        return $this->repository->create($input->toArray());
    }

    /**
     * 删除领导.
     * @param array<int> $userIds
     */
    public function delete(int $deptId, array $userIds): void
    {
        $this->repository->deleteByDoubleKey($deptId, $userIds);
    }
}
