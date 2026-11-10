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

namespace Plugin\Distribution\Infrastructure\Listener;

use App\Domain\Member\Event\MemberRegistered;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\Distribution\Application\Registration\AppDistributionRegistrationService;

#[Listener(priority: 100)]
final class MemberRegisteredListener implements ListenerInterface
{
    public function __construct(private readonly AppDistributionRegistrationService $registrations) {}

    public function listen(): array
    {
        return [MemberRegistered::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof MemberRegistered) {
            $this->registrations->bindRegisteredMember($event);
        }
    }
}
