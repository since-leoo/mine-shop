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

use App\Domain\Member\Event\OrderPaidForMember;
use App\Domain\Member\Repository\MemberRepository;
use Hyperf\Event\Contract\ListenerInterface;

final class SyncMemberOrderStatsListener implements ListenerInterface
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
    ) {}

    public function listen(): array
    {
        return [
            OrderPaidForMember::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof OrderPaidForMember) {
            return;
        }

        $this->memberRepository->syncOrderStats($event->memberId);
    }
}
