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

namespace App\Application\Admin\Member;

use App\Domain\Member\Service\DomainMemberLevelService;

final class AppMemberLevelQueryService
{
    public function __construct(private readonly DomainMemberLevelService $memberLevelService) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->memberLevelService->page($filters, $page, $pageSize);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function find(int $id): ?array
    {
        $level = $this->memberLevelService->findById($id);
        return $level?->toArray();
    }
}
