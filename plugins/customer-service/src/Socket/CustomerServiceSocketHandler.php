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

namespace Plugin\CustomerService\Socket;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Plugin\CustomerService\Application\Socket\CustomerServiceSocketTicketService;
use Plugin\CustomerService\Service\CustomerServiceRealtimeService;

final class CustomerServiceSocketHandler implements OnOpenInterface, OnMessageInterface, OnCloseInterface
{
    public function __construct(
        private readonly CustomerServiceConnectionTable $connections,
        private readonly CustomerServiceSocketProtocol $protocol,
        private readonly CustomerServiceSocketResponder $responder,
        private readonly CustomerServiceSocketEventDispatcher $dispatcher,
        private readonly CustomerServiceSocketTicketService $tickets,
        private readonly CustomerServiceRealtimeService $realtime,
    ) {}

    public function onOpen($server, $request): void
    {
        $this->responder->send($server, $request->fd, 'connected', ['fd' => $request->fd]);
    }

    public function onMessage($server, $frame): void
    {
        $payload = $this->protocol->decode((string) $frame->data);
        if ($payload === null) {
            $this->responder->error($server, $frame->fd, '消息格式无效');
            return;
        }

        $event = (string) ($payload['event'] ?? '');
        if ($event === 'ping') {
            $this->responder->send($server, $frame->fd, 'pong', ['at' => time()]);
            return;
        }

        if ($event === 'auth') {
            $ticket = (string) ($payload['payload']['ticket'] ?? '');
            $principal = $this->tickets->consume($ticket);
            if ($principal === null || ! \in_array($principal['principal_type'], ['member', 'agent'], true)) {
                $this->responder->error($server, $frame->fd, 'Socket 凭证无效或已过期');
                return;
            }
            $this->connections->set((string) $frame->fd, ['principal_type' => $principal['principal_type'], 'principal_id' => (int) $principal['principal_id'], 'admin_user_id' => (int) ($principal['admin_user_id'] ?? 0), 'connected_at' => time()]);
            $this->realtime->online($principal['principal_type'], (int) $principal['principal_id']);
            $this->responder->send($server, $frame->fd, 'authenticated', ['principal_type' => $principal['principal_type'], 'principal_id' => (int) $principal['principal_id']]);
            return;
        }

        $connection = $this->connections->get((string) $frame->fd);
        $principalId = \is_array($connection) ? (int) ($connection['principal_id'] ?? 0) : 0;
        if ($principalId <= 0) {
            $this->responder->error($server, $frame->fd, '请先认证 Socket 连接');
            return;
        }

        $this->dispatcher->dispatch($server, $frame->fd, (string) $connection['principal_type'], $principalId, (int) ($connection['admin_user_id'] ?? 0), $payload);
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        $connection = $this->connections->get((string) $fd);
        if ($connection !== null) {
            $this->realtime->offline((string) $connection['principal_type'], (int) $connection['principal_id']);
        }
        $this->connections->del((string) $fd);
    }
}
