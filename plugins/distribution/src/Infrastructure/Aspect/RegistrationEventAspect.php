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

namespace Plugin\Distribution\Infrastructure\Aspect;

use App\Domain\Member\Event\MemberRegistered;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Event\EventDispatcher;
use Plugin\Distribution\Application\Registration\AppDistributionRegistrationService;
use Plugin\Distribution\Infrastructure\Listener\MemberRegisteredListener;

/** 仅注册事务中的通用事件延后派发，不影响其他业务事件。 */
#[Aspect]
final class RegistrationEventAspect extends AbstractAspect
{
    public array $classes = [EventDispatcher::class . '::dispatch'];

    public function __construct(private readonly AppDistributionRegistrationService $registrations, private readonly MemberRegisteredListener $listener) {}

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $event = $proceedingJoinPoint->arguments['keys']['event'] ?? null;
        if ($event instanceof MemberRegistered && $this->registrations->current()) {
            $this->listener->process($event);
            return $event;
        }
        return $proceedingJoinPoint->process();
    }
}
