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

namespace App\Application\Api\Cart;

use App\Domain\Member\Api\Query\DomainApiMemberCartQueryService;

final class AppApiCartQueryService
{
    public function __construct(
        private readonly DomainApiMemberCartQueryService $cartQueryService
    ) {}

    /**
     * 返回购物车原始数据（含 sku/product 快照）.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDetailed(int $memberId): array
    {
        return $this->cartQueryService->listDetailed($memberId);
    }
}
