<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Socket;

use App\Infrastructure\Error\CustomerServiceError;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Plugin\CustomerService\Application\Socket\CustomerServiceSocketTicketService;
use Plugin\CustomerService\Domain\Contract\CustomerServiceAuthorizationInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServicePresenceInterface;

final class CustomerServiceSocketHandler implements OnOpenInterface, OnMessageInterface, OnCloseInterface
{
    public function __construct(
        private readonly CustomerServiceConnectionTable $connections,
        private readonly CustomerServiceSocketProtocol $protocol,
        private readonly CustomerServiceSocketResponder $responder,
        private readonly CustomerServiceSocketEventDispatcher $dispatcher,
        private readonly CustomerServiceSocketTicketService $tickets,
        private readonly CustomerServicePresenceInterface $presence,
        private readonly CustomerServiceAuthorizationInterface $authorization,
    ) {}

    /** @param object $server */
    public function onOpen($server, $request): void
    {
        $this->responder->send($server, $request->fd, 'connected', ['fd' => $request->fd]);
    }

    public function onMessage($server, $frame): void
    {
        $message = $this->protocol->decode((string) $frame->data);
        if ($message === null) {
            $this->responder->error($server, $frame->fd, '消息格式无效');
            return;
        }
        try {
            $connection = $this->connections->get((string) $frame->fd);
            if ($message['event'] === 'auth') {
                if ($connection !== null) {
                    throw CustomerServiceError::SOCKET_ALREADY_AUTHENTICATED->exception();
                }
                $principal = $this->tickets->consume((string) ($message['payload']['ticket'] ?? ''));
                if ($principal === null) {
                    throw CustomerServiceError::SOCKET_TICKET_INVALID->exception();
                }
                if ($principal['principal_type'] === 'agent') {
                    $this->authorization->authorizeAgentAction((int) $principal['admin_user_id'], (int) $principal['principal_id'], 'auth');
                }
                $connection = $principal + ['admin_user_id' => 0, 'connected_at' => time(), 'connection_id' => bin2hex(random_bytes(24))];
                $this->presence->refresh($connection['principal_type'], (int) $connection['principal_id'], $connection['connection_id']);
                $this->connections->set((string) $frame->fd, $connection);
                $this->responder->send($server, $frame->fd, 'authenticated', ['principal_type' => $connection['principal_type'], 'principal_id' => (int) $connection['principal_id']]);
                return;
            }
            if ($connection === null) {
                throw CustomerServiceError::SOCKET_AUTHENTICATION_REQUIRED->exception();
            }
            if ($message['event'] === 'ping') {
                if ($connection['principal_type'] === 'agent') {
                    $this->authorization->authorizeAgentAction((int) $connection['admin_user_id'], (int) $connection['principal_id'], 'ping');
                }
                $this->presence->refresh($connection['principal_type'], (int) $connection['principal_id'], $connection['connection_id']);
                $this->responder->send($server, $frame->fd, 'pong', ['at' => time()]);
                return;
            }
            $this->dispatcher->dispatch($server, $frame->fd, $connection['principal_type'], (int) $connection['principal_id'], (int) $connection['admin_user_id'], $message);
        } catch (\DomainException|\InvalidArgumentException $exception) {
            $this->responder->error($server, $frame->fd, $exception->getMessage());
        } catch (\Throwable) {
            $this->responder->error($server, $frame->fd, '客服连接暂不可用，请重试');
        }
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        $connection = $this->connections->get((string) $fd);
        $this->connections->del((string) $fd);
        if ($connection !== null) {
            try {
                $this->presence->disconnect($connection['principal_type'], (int) $connection['principal_id'], $connection['connection_id']);
            } catch (\Throwable) {
                // The bounded lease expires even if Redis is unavailable during disconnect.
            }
        }
    }
}
