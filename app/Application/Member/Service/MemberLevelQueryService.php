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

namespace App\Application\Member\Service;

use App\Domain\Member\Service\MemberLevelService;

final class MemberLevelQueryService
{
    public function __construct(private readonly MemberLevelService $memberLevelService) {}

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
        $level = $this->memberLevelService->find($id);
        return $level?->toArray();
    }
}
