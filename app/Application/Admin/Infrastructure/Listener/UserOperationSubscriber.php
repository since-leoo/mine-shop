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

namespace App\Application\Admin\Infrastructure\Listener;

use App\Application\Admin\Infrastructure\AppUserOperationLogCommandService;
use App\Application\Admin\Permission\AppUserQueryService;
use App\Domain\Infrastructure\AuditLog\Service\AuditModeContextEnricher;
use App\Interface\Common\Event\RequestOperationEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class UserOperationSubscriber implements ListenerInterface
{
    public function __construct(
        private readonly AppUserOperationLogCommandService $service,
        private readonly AppUserQueryService $userQueryService,
        private readonly AuditModeContextEnricher $auditModeContextEnricher
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
            $payload = [
                'username' => $user->username,
                'method' => $event->getMethod(),
                'router' => $event->getPath(),
                'remark' => $event->getRemark(),
                'ip' => $event->getIp(),
                'service_name' => $event->getOperation(),
            ];
            $context = [
                'user_id' => $userId,
                'operation' => $event->getOperation(),
                'path' => $event->getPath(),
                'method' => $event->getMethod(),
                'ip' => $event->getIp(),
            ];
            $handler = fn () => $this->service->create($this->auditModeContextEnricher->enrich($payload, $context));

            if (class_exists(\Swoole\Coroutine::class)) {
                \Hyperf\Engine\Coroutine::create($handler);
                return;
            }

            $handler();
        }
    }
}
