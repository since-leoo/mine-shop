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

namespace App\Domain\Member\Listener;

use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Stringable\Str;

final class RecordMemberBalanceLogListener implements ListenerInterface
{
    public function __construct(
        private readonly MemberWalletTransactionRepository $transactionRepository,
    ) {}

    public function listen(): array
    {
        return [
            MemberBalanceAdjusted::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof MemberBalanceAdjusted) {
            return;
        }

        \Hyperf\Coroutine\co(function () use ($event) {
            $type = $event->changeAmount >= 0 ? 'adjust_in' : 'adjust_out';
            $amount = abs($event->changeAmount);

            $this->transactionRepository->create([
                'wallet_id' => $event->walletId,
                'member_id' => $event->memberId,
                'wallet_type' => $event->walletType,
                'transaction_no' => Str::upper(Str::random(24)),
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $event->beforeBalance,
                'balance_after' => $event->afterBalance,
                'source' => $event->source,
                'related_type' => $event->relatedType,
                'related_id' => $event->relatedId,
                'description' => $event->remark ?: '后台调整',
                'remark' => $event->remark,
                'operator_type' => (string) ($event->operator['type'] ?? 'admin'),
                'operator_id' => $event->operator['id'] ?? null,
                'operator_name' => $event->operator['name'] ?? null,
            ]);
        });
    }
}
