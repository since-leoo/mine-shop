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

namespace App\Domain\Infrastructure\AuditLog\Service;

use App\Domain\Infrastructure\AuditLog\Repository\UserOperationLogRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 用户操作日志领域服务.
 */
final class DomainUserOperationLogService extends IService
{
    public function __construct(
        public readonly UserOperationLogRepository $repository
    ) {}

    /**
     * 创建操作日志.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->repository->create($payload);
    }

    /**
     * 删除操作日志.
     */
    public function delete(mixed $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }
}
