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

namespace App\Domain\Logstash\Listener;

use App\Domain\Logstash\Service\DomainUserLoginLogService;
use Hyperf\Engine\Coroutine;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Mine\JwtAuth\Event\UserLoginEvent;

#[Listener]
class UserLoginSubscriber implements ListenerInterface
{
    public function __construct(
        private readonly DomainUserLoginLogService $service
    ) {}

    public function listen(): array
    {
        return [
            UserLoginEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof UserLoginEvent) {
            $user = $event->getUser();
            Coroutine::create(fn () => $this->service->create([
                'username' => $user->username,
                'ip' => $event->getIp(),
                'os' => $event->getOs(),
                'browser' => $event->getBrowser(),
                'status' => $event->isLogin() ? 1 : 2,
            ]));
        }
    }
}
