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

namespace App\Domain\Member\Service;

use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Infrastructure\Abstract\IService;

/**
 * 会员钱包流水领域服务：封装流水查询逻辑.
 */
final class DomainMemberWalletTransactionService extends IService
{
    public function __construct(
        public readonly MemberWalletTransactionRepository $repository,
    ) {}
}
