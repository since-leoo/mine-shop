<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

final class DomainRoutingService
{
    public function __construct(private readonly DomainCategoryService $categories) {}

    public function candidates(array $agents, array $snapshot, ?int $categoryId, \DateTimeImmutable $now): array
    {
        $eligible = [];
        foreach ($agents as $agent) {
            if (!($agent['enabled'] ?? true) || $agent['status'] !== 'online' || (int) $agent['max_conversations'] <= (int) $agent['current_conversations']) {
                continue;
            }
            if (empty($agent['last_heartbeat_at']) || strtotime((string) $agent['last_heartbeat_at']) < $now->getTimestamp() - 90 || !$this->onDuty($agent['schedule_json'] ?? [], $now)) {
                continue;
            }
            $rank = $this->skillRank((int) $agent['id'], $snapshot, $categoryId);
            if ($rank === null) { continue; }
            $agent['_rank'] = $rank;
            $eligible[] = $agent;
        }
        $strategy = $snapshot['settings']['assignment_strategy'] ?? 'least_load';
        usort($eligible, static function ($a, $b) use ($strategy) {
            $prefixA = [...$a['_rank'], -(int) ($a['priority'] ?? 0)];
            $prefixB = [...$b['_rank'], -(int) ($b['priority'] ?? 0)];
            $loadA = (int) $a['current_conversations'] / max(1, (int) $a['max_conversations']);
            $loadB = (int) $b['current_conversations'] / max(1, (int) $b['max_conversations']);
            if ($strategy === 'weighted') {
                $loadA /= max(1, (int) ($a['weight'] ?? 1));
                $loadB /= max(1, (int) ($b['weight'] ?? 1));
            }
            return [...$prefixA, $strategy === 'round_robin' ? 0 : $loadA, $a['last_assigned_at'] ?? '', $a['id']] <=> [...$prefixB, $strategy === 'round_robin' ? 0 : $loadB, $b['last_assigned_at'] ?? '', $b['id']];
        });
        return $eligible;
    }

    /** 自动分配与人工访问共用技能规则；在线租约和容量由分配阶段另行检查。 */
    public function skillRank(int $agentId, array $snapshot, ?int $categoryId): ?array
    {
        $category = $categoryId ? $this->categories->effective($snapshot['categories'] ?? [], $categoryId) : null;
        $groups = array_column($snapshot['skill_groups'] ?? [], null, 'id');
        $groupId = (int) ($category['default_group_id'] ?? $snapshot['settings']['fallback_group_id'] ?? 0);
        $requestedGroup = $groupId;
        $groupIds = [];
        while ($groupId && isset($groups[$groupId]) && !in_array($groupId, $groupIds, true)) {
            if ($groups[$groupId]['enabled'] ?? true) {
                $groupIds[] = $groupId;
            }
            $groupId = (int) ($groups[$groupId]['fallback_group_id'] ?? 0);
        }
        if ($categoryId && !$category) { return null; }
        $skills = array_filter($snapshot['agent_skills'] ?? [], static fn ($skill) => (int) $skill['agent_id'] === $agentId && ($skill['enabled'] ?? true));
        $rank = null;
        foreach ($skills as $skill) {
            $group = $groups[(int) $skill['group_id']] ?? null;
            if (!$group || !($group['enabled'] ?? true)) { continue; }
            $skillCategory = (int) ($skill['category_id'] ?? 0);
            if ($skillCategory && (!$category || ($skillCategory !== $categoryId && (!(bool) ($skill['include_descendants'] ?? false) || !in_array($skillCategory, $category['path'], true))))) { continue; }
            // 显式配置了目标组却没有可用兜底组时，不能退回任意坐席。
            if ($requestedGroup && !$groupIds) { continue; }
            $groupRank = $groupIds ? array_search((int) $skill['group_id'], $groupIds, true) : 0;
            if ($groupRank !== false) {
                $skillRank = [$groupRank, $skillCategory === $categoryId ? 0 : ($skillCategory ? 1 : 2), -(int) ($skill['priority'] ?? 0)];
                $rank = $rank === null || $skillRank < $rank ? $skillRank : $rank;
            }
        }
        $configured = !empty($snapshot['agent_skills']) || !empty($snapshot['categories']) || !empty($snapshot['skill_groups']) || !empty($snapshot['settings']['fallback_group_id']);
        return $rank ?? ($configured ? null : [0, 0, 0]);
    }

    public function onDuty(array $schedule, \DateTimeImmutable $now): bool
    {
        if (!$schedule) {
            return true;
        }
        $local = $now->setTimezone(new \DateTimeZone($schedule['timezone'] ?? 'Asia/Shanghai'));
        $slots = $schedule['exceptions'][$local->format('Y-m-d')] ?? $schedule['weekly'][$local->format('N')] ?? [];
        foreach ($slots as $slot) {
            if ($local->format('H:i') >= $slot[0] && $local->format('H:i') < $slot[1]) {
                return true;
            }
        }
        return false;
    }
}
