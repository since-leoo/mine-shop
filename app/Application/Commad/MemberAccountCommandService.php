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

namespace App\Application\Commad;

use App\Domain\Member\Contract\MemberWalletInput;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Service\MemberWalletService;
use Hyperf\DbConnection\Db;

final class MemberAccountCommandService
{
    public function __construct(
        private readonly MemberWalletService $accountService
    ) {}

    /**
     * 调整余额.
     *
     * @param array{type?:string,id?:null|int,name?:null|string} $operator
     * @return array<string, mixed>
     */
    public function adjustBalance(MemberWalletInput $input, array $operator = []): array
    {
        // 事务管理
        return Db::transaction(function () use ($input, $operator) {
            // 调用领域服务
            $vo = $this->accountService->adjustBalance($input);

            // 发布领域事件
            $event = new MemberBalanceAdjusted(
                memberId: $vo->memberId,
                walletId: null,
                walletType: $vo->walletType,
                changeAmount: $vo->changeAmount,
                beforeBalance: $vo->beforeBalance,
                afterBalance: $vo->afterBalance,
                source: $input->getSource(),
                remark: $input->getRemark(),
                operator: $operator,
            );
            event($event);

            return [
                'member_id' => $vo->memberId,
                'wallet_type' => $vo->walletType,
                'before_balance' => $vo->beforeBalance,
                'after_balance' => $vo->afterBalance,
                'change_amount' => $vo->changeAmount,
            ];
        });
    }
}
