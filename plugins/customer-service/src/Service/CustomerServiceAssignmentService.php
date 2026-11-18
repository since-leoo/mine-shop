<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Service;

use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Mapper\CustomerServiceConversationMapper;
use Plugin\CustomerService\Domain\Service\DomainRoutingService;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversationAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversationLog;

final class CustomerServiceAssignmentService
{
    public function __construct(private readonly CustomerServiceSettingsResolver $settings, private readonly SupportConfigurationInterface $configuration, private readonly DomainRoutingService $routing, private readonly CustomerServiceConversationMapper $mapper, private readonly \Plugin\CustomerService\Domain\Contract\CustomerServicePresenceInterface $presence) {}

    /** 调用方已锁定会话；每个候选坐席都须加行锁后再次检查，容量预占才不会超卖。 */
    public function assignWaitingConversation(CustomerServiceConversation $conversation): ?CustomerServiceAgent
    {
        $settings = $this->settings->toArray();
        if (!$settings['enabled'] || $conversation->reception_state !== 'human_waiting' || $settings['assignment_strategy'] === 'manual') { return null; }
        $snapshot = $this->configuration->published()['snapshot'];
        $snapshot['settings'] = $settings;
        if ($conversation->ai_agent_id) {
            $aiAgent = array_column($snapshot['ai_agents'] ?? [], null, 'id')[(int) $conversation->ai_agent_id] ?? [];
            $snapshot['settings']['fallback_group_id'] = $aiAgent['fallback_group_id'] ?? $settings['fallback_group_id'];
        }
        $now = new \DateTimeImmutable();
        if (!$this->routing->onDuty($settings['schedule'] ?? [], $now)) {
            $entity = $this->mapper->fromModel($conversation);
            $entity->defer('outside_business_hours', $now->format('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
            return null;
        }
        $agents = CustomerServiceAgent::query()->where('status', 'online')->where('enabled', true)->get()->toArray();
        $candidates = $this->routing->candidates($agents, $snapshot, $conversation->category_id, $now);
        foreach ($candidates as $candidate) {
            $agent = CustomerServiceAgent::query()->whereKey($candidate['id'])->lockForUpdate()->first();
            if (!$agent || !$this->presence->isOnline((int) $agent->id) || !$this->routing->candidates([$agent->toArray()], $snapshot, $conversation->category_id, $now)) { continue; }
            $entity = $this->mapper->fromModel($conversation);
            $entity->assign((int) $agent->id, $now->modify('+' . (int) $settings['accept_timeout_seconds'] . ' seconds')->format('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
            $agent->update(['current_conversations' => $agent->current_conversations + 1]);
            $agent->update(['last_assigned_at' => $now->format('Y-m-d H:i:s')]);
            $this->recordAgent($conversation, (int) $agent->id, 'assigned', null, 'routing');
            $this->log($conversation, 'assigned', 'system', null, ['agent_id' => $agent->id, 'category_id' => $conversation->category_id, 'config_revision' => $this->configuration->published()['revision']]);
            return $agent;
        }
        $waiting = CustomerServiceConversation::query()->where('reception_state', 'human_waiting')->count();
        if (!$settings['queue_enabled'] || $waiting > $settings['max_queue_size']) {
            $entity = $this->mapper->fromModel($conversation);
            $entity->defer($settings['queue_enabled'] ? 'queue_full' : 'queue_disabled', $now->format('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
        }
        return null;
    }

    public function recordAgent(CustomerServiceConversation $conversation, int $agentId, string $action, ?int $operatorId, ?string $reason = null): void
    {
        CustomerServiceConversationAgent::query()->create(['conversation_id' => $conversation->id, 'agent_id' => $agentId, 'action' => $action, 'operator_admin_user_id' => $operatorId, 'reason' => $reason]);
    }

    public function release(CustomerServiceConversation $conversation): void
    {
        if ($conversation->assigned_agent_id) { CustomerServiceAgent::query()->whereKey($conversation->assigned_agent_id)->where('current_conversations', '>', 0)->decrement('current_conversations'); }
    }

    public function log(CustomerServiceConversation $conversation, string $action, string $operatorType, ?int $operatorId, array $detail = []): void
    {
        CustomerServiceConversationLog::query()->create(['conversation_id' => $conversation->id, 'action' => $action, 'operator_type' => $operatorType, 'operator_id' => $operatorId, 'detail_json' => $detail ?: null]);
    }
}
