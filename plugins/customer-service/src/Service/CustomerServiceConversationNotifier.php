<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use Plugin\CustomerService\Domain\Contract\CustomerServiceEventPublisherInterface;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;

final class CustomerServiceConversationNotifier
{
    public function __construct(private readonly CustomerServiceEventPublisherInterface $events) {}

    public function conversationChanged(CustomerServiceConversation $conversation, string $event): void
    {
        $context = ['conversation_no' => $conversation->conversation_no];
        $this->events->publish($event, $conversation->toArray(), 'member', (int) $conversation->member_id, $context);
        if ($conversation->assigned_agent_id) {
            $this->events->publish($event, $conversation->toArray(), 'agent', (int) $conversation->assigned_agent_id, $context);
        }
        $this->events->publish('conversation:queued', [], 'agent');
    }

    public function messageCreated(CustomerServiceConversation $conversation, array $message): void
    {
        $context = ['conversation_no' => $conversation->conversation_no, 'client_message_id' => $message['client_message_id'] ?? null];
        if (($message['visibility'] ?? 'public') !== 'internal') {
            $this->events->publish('message:created', $message, 'member', (int) $conversation->member_id, $context);
        }
        if ($conversation->assigned_agent_id) {
            $this->events->publish('message:created', $message, 'agent', (int) $conversation->assigned_agent_id, $context);
        }
    }
}
