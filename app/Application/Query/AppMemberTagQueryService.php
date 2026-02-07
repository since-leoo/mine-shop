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

namespace App\Application\Query;

use App\Domain\Member\Service\DomainMemberTagService;

final class AppMemberTagQueryService
{
    public function __construct(private readonly DomainMemberTagService $memberTagService) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->memberTagService->page($filters, $page, $pageSize);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        return $this->memberTagService->getList($filters)->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this->memberTagService->activeOptions();
    }
}
