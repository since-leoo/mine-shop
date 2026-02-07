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

final class AppUserLoginLogQueryService
{
    public function __construct(private readonly DomainUserLoginLogService $userLoginLogService) {}

    /**
     * 分页查询用户登录日志.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->userLoginLogService->page($filters, $page, $pageSize);
    }
}
