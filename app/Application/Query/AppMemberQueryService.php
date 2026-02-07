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

use App\Domain\Member\Service\DomainMemberService;

/**
 * 会员查询应用服务.
 */
final class AppMemberQueryService
{
    public function __construct(private readonly DomainMemberService $memberService) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->memberService->page($filters, $page, $pageSize);
    }

    /**
     * @return array<string, int>
     */
    public function stats(array $filters): array
    {
        return $this->memberService->stats($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        return $this->memberService->overview($filters);
    }

    /**
     * @return null|array<string, mixed>
     */
    public function detail(int $id): ?array
    {
        return $this->memberService->detail($id);
    }
}
