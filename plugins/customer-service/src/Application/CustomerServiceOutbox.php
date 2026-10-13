<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application;

use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;

final class CustomerServiceOutbox
{
    public function publish(string $event, array $payload, ?string $principalType = null, ?int $principalId = null, array $context = []): void
    {
        $eventId = bin2hex(random_bytes(16));
        Db::table('customer_service_outbox_events')->insert([
            'event_id' => $eventId, 'event' => $event, 'payload_json' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'principal_type' => $principalType, 'principal_id' => $principalId,
            'context_json' => json_encode($context + ['event_id' => $eventId], JSON_THROW_ON_ERROR),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function conversationChanged(CustomerServiceConversation $conversation, string $event = 'conversation:updated'): void
    {
        $this->publish($event, $conversation->toArray(), 'member', (int) $conversation->member_id, ['conversation_no' => $conversation->conversation_no]);
        // Queue refresh contains no member/chat data; each agent re-queries its authorized scope.
        $this->publish('conversation:queued', ['refresh' => true], 'agent');
        if ($conversation->assigned_agent_id) {
            $this->publish($event, $conversation->toArray(), 'agent', (int) $conversation->assigned_agent_id, ['conversation_no' => $conversation->conversation_no]);
        }
    }

    public function messageCreated(CustomerServiceConversation $conversation, array $message): void
    {
        if (($message['visibility'] ?? 'public') !== 'public') {
            if ($conversation->assigned_agent_id) { $this->publish('message:created', $message, 'agent', (int) $conversation->assigned_agent_id, ['conversation_no' => $conversation->conversation_no]); }
            return;
        }
        $context = ['conversation_no' => $conversation->conversation_no, 'client_message_id' => $message['client_message_id']];
        $this->publish('message:created', $message, 'member', (int) $conversation->member_id, $context);
        if ($conversation->assigned_agent_id) { $this->publish('message:created', $message, 'agent', (int) $conversation->assigned_agent_id, $context); }
    }
}
