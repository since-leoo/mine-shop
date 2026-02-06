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

namespace App\Domain\Member\Event;

/**
 * 会员钱包变更事件.
 */
final class MemberBalanceAdjusted
{
    /**
     * @param array{type?:string,id?:null|int,name?:null|string} $operator
     */
    public function __construct(
        public readonly int $memberId,
        public readonly ?int $walletId,
        public readonly string $walletType,
        public readonly float $changeAmount,
        public readonly float $beforeBalance,
        public readonly float $afterBalance,
        public readonly string $source = 'manual',
        public readonly string $remark = '',
        public readonly array $operator = [],
        public readonly ?string $relatedType = null,
        public readonly ?int $relatedId = null,
    ) {}
}
