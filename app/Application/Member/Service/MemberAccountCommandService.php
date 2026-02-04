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

use App\Application\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Entity\MemberWalletEntity;
use App\Domain\Member\Service\MemberWalletService;
use Hyperf\DbConnection\Db;
use Psr\EventDispatcher\EventDispatcherInterface;

final class MemberAccountCommandService
{
    public function __construct(
        private readonly MemberWalletService $accountService,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @param array{type?:string,id?:null|int,name?:null|string} $operator
     * @return array<string, mixed>
     */
    public function adjustBalance(MemberWalletEntity $wallet, array $operator = []): array
    {
        return Db::transaction(function () use ($wallet, $operator) {
            $updated = $this->accountService->adjustBalance($wallet);

            $event = new MemberBalanceAdjusted(
                memberId: $updated->getMemberId(),
                walletId: $updated->getId(),
                walletType: $updated->getType(),
                changeAmount: $wallet->getChangeBalance(),
                beforeBalance: $updated->getBeforeBalance(),
                afterBalance: $updated->getAfterBalance(),
                source: $wallet->getSource(),
                remark: $wallet->getRemark(),
                operator: $operator,
            );
            $this->dispatcher->dispatch($event);

            return [
                'member_id' => $updated->getMemberId(),
                'wallet_type' => $updated->getType(),
                'before_balance' => $updated->getBeforeBalance(),
                'after_balance' => $updated->getAfterBalance(),
            ];
        });
    }
}
