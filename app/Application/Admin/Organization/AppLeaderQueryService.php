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

use App\Domain\Organization\Service\DomainLeaderService;

final class AppLeaderQueryService
{
    public function __construct(private readonly DomainLeaderService $leaderService) {}

    /**
     * 分页查询领导.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->leaderService->page($filters, $page, $pageSize);
    }
}
