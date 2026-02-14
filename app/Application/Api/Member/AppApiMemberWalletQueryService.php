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

namespace App\Application\Api\Member;

use App\Domain\Member\Service\DomainMemberWalletTransactionService;

final class AppApiMemberWalletQueryService
{
    public function __construct(
        private readonly DomainMemberWalletTransactionService $walletTransactionService,
    ) {}

    /**
     * 查询会员钱包流水（分页）.
     *
     * @param int $memberId 当前登录会员ID
     * @param string $walletType 钱包类型（balance/points）
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array{list: array, total: int}
     */
    public function transactions(int $memberId, string $walletType, int $page, int $pageSize): array
    {
        return $this->walletTransactionService->page(
            ['member_id' => $memberId, 'wallet_type' => $walletType],
            $page,
            $pageSize
        );
    }
}
