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

use App\Domain\Infrastructure\AuditLog\Service\DomainUserLoginLogService;

final class AppUserLoginLogCommandService
{
    public function __construct(private readonly DomainUserLoginLogService $service) {}

    public function delete(mixed $ids): int
    {
        return $this->service->delete($ids);
    }
}
