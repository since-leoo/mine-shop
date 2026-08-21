<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Swoole\WebSocket\Server;

final class CustomerServiceConversationNotifier
{
    public function __construct(private readonly CustomerServiceRealtimeService $realtime) {}

    public function queued(Server $server, CustomerServiceConversation $conversation): void
    {
        if ($conversation->assigned_agent_id) {
            $this->realtime->broadcast($server, 'conversation:assigned', $conversation->toArray(), 'agent', (int) $conversation->assigned_agent_id);
            return;
        }
        $this->realtime->broadcast($server, 'conversation:queued', $conversation->toArray(), 'agent');
    }

    public function accepted(Server $server, CustomerServiceConversation $conversation): void
    {
        $this->realtime->broadcast($server, 'conversation:accepted', $conversation->toArray(), 'member', (int) $conversation->member_id);
    }

    public function memberMessage(Server $server, CustomerServiceConversation $conversation, array $message): void
    {
        if ($conversation->assigned_agent_id) {
            $this->realtime->broadcast($server, 'message:created', $message, 'agent', (int) $conversation->assigned_agent_id, ['conversation_no' => $conversation->conversation_no]);
        }
    }

    public function agentMessage(Server $server, CustomerServiceConversation $conversation, array $message): void
    {
        $this->realtime->broadcast($server, 'message:created', $message, 'member', (int) $conversation->member_id, ['conversation_no' => $conversation->conversation_no]);
    }

    public function closed(Server $server, CustomerServiceConversation $conversation): void
    {
        $this->realtime->broadcast($server, 'conversation:closed', ['conversation_no' => $conversation->conversation_no], 'member', (int) $conversation->member_id);
    }

    public function transferred(Server $server, string $conversationNo, int $targetAgentId): void
    {
        $this->realtime->broadcast($server, 'conversation:transferred', ['conversation_no' => $conversationNo], 'agent', $targetAgentId);
    }
}
