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

namespace App\Application\Admin\Infrastructure;

use App\Domain\Infrastructure\AuditLog\Service\DomainUserOperationLogService;

final class AppUserOperationLogCommandService
{
    public function __construct(private readonly DomainUserOperationLogService $userOperationLogService) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): mixed
    {
        return $this->userOperationLogService->create($payload);
    }

    public function delete(mixed $ids): int
    {
        return $this->userOperationLogService->delete($ids);
    }
}
