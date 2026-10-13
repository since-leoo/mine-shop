<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

final class DomainConversationAccessService
{
    public function __construct(private readonly DomainCategoryService $categories) {}

    public function allowedCategories(int $agentId, array $snapshot): ?array
    {
        if (empty($snapshot['agent_skills']) && empty($snapshot['categories']) && empty($snapshot['skill_groups']) && empty($snapshot['settings']['fallback_group_id'])) { return null; }
        $routing = new DomainRoutingService($this->categories);
        $allowed = [];
        foreach ([null, ...array_column($snapshot['categories'] ?? [], 'id')] as $categoryId) {
            if ($routing->skillRank($agentId, $snapshot, $categoryId) !== null) { $allowed[] = (int) $categoryId; }
        }
        return $allowed;
    }

    public function routingSnapshot(array $conversation, array $snapshot): array
    {
        $agent = array_column($snapshot['ai_agents'] ?? [], null, 'id')[(int) ($conversation['ai_agent_id'] ?? 0)] ?? [];
        if (!empty($agent['fallback_group_id'])) { $snapshot['settings']['fallback_group_id'] = $agent['fallback_group_id']; }
        return $snapshot;
    }

    public function canRead(array $conversation, int $agentId, array $snapshot, bool $supervisor = false): bool
    {
        if ($supervisor || ($agentId > 0 && (int) ($conversation['assigned_agent_id'] ?? 0) === $agentId)) { return true; }
        if (!empty($conversation['assigned_agent_id']) || $conversation['reception_state'] === 'closed') { return false; }
        $allowed = $this->allowedCategories($agentId, $this->routingSnapshot($conversation, $snapshot));
        return $allowed === null || in_array((int) ($conversation['category_id'] ?? 0), $allowed, true);
    }
}
