<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversationAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversationLog;

final class CustomerServiceAssignmentService
{
    public function __construct(private readonly CustomerServiceSettingsResolver $settings) {}

    public function assignWaitingConversation(CustomerServiceConversation $conversation): ?CustomerServiceAgent
    {
        $config = $this->settings->toArray();
        if (! $config['queue_enabled'] || $config['auto_assign_strategy'] !== 'least_load') {
            return null;
        }
        $agent = CustomerServiceAgent::query()->where('status', 'online')
            ->whereColumn('current_conversations', '<', 'max_conversations')
            ->orderBy('current_conversations')->orderBy('id')->lockForUpdate()->first();
        if ($agent === null) {
            return null;
        }
        $conversation->update(['assigned_agent_id' => $agent->id, 'status' => 'assigned']);
        $agent->increment('current_conversations');
        $this->recordAgent($conversation, (int) $agent->id, 'assigned', null, 'auto_assign');
        $this->log($conversation, 'assigned', 'system', null, ['agent_id' => $agent->id, 'strategy' => 'least_load']);
        return $agent;
    }

    public function recordAgent(CustomerServiceConversation $conversation, int $agentId, string $action, ?int $operatorId, ?string $reason = null): void
    {
        CustomerServiceConversationAgent::query()->create(['conversation_id' => $conversation->id, 'agent_id' => $agentId, 'action' => $action, 'operator_admin_user_id' => $operatorId, 'reason' => $reason]);
    }

    public function release(CustomerServiceConversation $conversation): void
    {
        if (! $conversation->assigned_agent_id) {
            return;
        }
        CustomerServiceAgent::query()->whereKey($conversation->assigned_agent_id)
            ->where('current_conversations', '>', 0)->decrement('current_conversations');
    }

    public function log(CustomerServiceConversation $conversation, string $action, string $operatorType, ?int $operatorId, array $detail = []): void
    {
        CustomerServiceConversationLog::query()->create(['conversation_id' => $conversation->id, 'action' => $action, 'operator_type' => $operatorType, 'operator_id' => $operatorId, 'detail_json' => $detail ?: null]);
    }
}
