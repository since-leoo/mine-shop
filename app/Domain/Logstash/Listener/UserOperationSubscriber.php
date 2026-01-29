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

use App\Application\Logstash\Service\UserOperationLogService;
use App\Application\Permission\Service\UserQueryService;
use App\Interface\Common\Event\RequestOperationEvent;
use Hyperf\Engine\Coroutine;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class UserOperationSubscriber implements ListenerInterface
{
    public function __construct(
        private readonly UserOperationLogService $service,
        private readonly UserQueryService $userQueryService
    ) {}

    public function listen(): array
    {
        return [
            RequestOperationEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof RequestOperationEvent) {
            $userId = $event->getUserId();
            $user = $this->userQueryService->find($userId);
            if (empty($user)) {
                return;
            }
            Coroutine::create(fn () => $this->service->create([
                'username' => $user->username,
                'method' => $event->getMethod(),
                'router' => $event->getPath(),
                'remark' => $event->getRemark(),
                'ip' => $event->getIp(),
                'service_name' => $event->getOperation(),
            ]));
        }
    }
}
