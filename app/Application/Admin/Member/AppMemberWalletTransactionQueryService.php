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

use App\Domain\Member\Service\DomainMemberWalletTransactionService;

final class AppMemberWalletTransactionQueryService
{
    public function __construct(
        private readonly DomainMemberWalletTransactionService $walletTransactionService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->walletTransactionService->page($filters, $page, $pageSize);
    }
}
