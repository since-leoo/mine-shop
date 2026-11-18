<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Mapper\CustomerServiceConversationMapper;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;
use Plugin\CustomerService\Service\CustomerServiceAssignmentService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class DomainConversationService
{
    public function __construct(private readonly CustomerServiceConversationRepository $repository, private readonly SupportConfigurationInterface $configuration, private readonly CustomerServiceSettingsResolver $settings, private readonly DomainCategoryService $categories, private readonly DomainKeywordService $keywords, private readonly CustomerServiceConversationMapper $mapper, private readonly CustomerServiceAssignmentService $assignment, private readonly DomainRoutingService $routing) {}

    public function open(int $memberId, string $source, ?string $subject, int $protocolVersion, ?int $categoryId): CustomerServiceConversation
    {
        $settings = $this->settings->toArray();
        if (!$settings['enabled']) { throw new \DomainException('客服服务暂未开启'); }
        if (mb_strlen($subject ?? '') > 120) { throw new \InvalidArgumentException('会话主题过长'); }
        $this->repository->lockMember($memberId);
        $conversation = $this->repository->findOpenByMember($memberId);
        if ($conversation) {
            if ($protocolVersion < 2 && $conversation->reception_state === 'ai_active') { $this->requestHuman($conversation, 'legacy_client'); }
            $this->assignment->assignWaitingConversation($conversation);
            return $conversation;
        }
        $published = $this->configuration->published();
        $snapshot = $published['snapshot'];
        $category = $categoryId ? $this->categories->effective($snapshot['categories'] ?? [], $categoryId) : null;
        if ($categoryId && !$category) { throw new \InvalidArgumentException('问题类目不存在或已停用'); }
        $agentId = (int) ($category['default_ai_agent_id'] ?? $settings['default_ai_agent_id'] ?? 0);
        $agent = array_column($snapshot['ai_agents'] ?? [], null, 'id')[$agentId] ?? null;
        $useAi = $protocolVersion >= 2 && $settings['ai_enabled'] && $agent && ($agent['enabled'] ?? true) && !($category['force_human'] ?? false);
        $conversation = $this->repository->createConversation($memberId, $source, $subject, [
            'reception_state' => $useAi ? 'ai_active' : 'human_waiting', 'config_revision' => $published['revision'],
            'category_id' => $categoryId, 'classification_json' => $category ? ['source' => 'user', 'path' => $category['path_names']] : null,
            'ai_agent_id' => $useAi ? $agentId : null, 'queue_entered_at' => $useAi ? null : date('Y-m-d H:i:s'),
            'priority' => (int) ($category['priority'] ?? 0),
        ]);
        $this->assignment->assignWaitingConversation($conversation);
        $this->assignment->log($conversation, 'opened', 'member', $memberId, ['protocol_version' => $protocolVersion]);
        return $conversation;
    }

    public function requestHuman(CustomerServiceConversation $conversation, string $reason): void
    {
        if (in_array($conversation->reception_state, ['human_waiting', 'human_assigned', 'human_active'], true)) { return; }
        $entity = $this->mapper->fromModel($conversation);
        $entity->requestHuman($reason, date('Y-m-d H:i:s'));
        $conversation->update($entity->toArray());
        $this->assignment->log($conversation, 'request_human', 'system', null, ['reason' => $reason]);
        $this->assignment->assignWaitingConversation($conversation);
    }

    public function classify(CustomerServiceConversation $conversation, int $categoryId, bool $human = false): void
    {
        if ($conversation->reception_state === 'closed') { throw new \DomainException('已关闭会话不能修改类目'); }
        $category = $this->categories->effective($this->configuration->published()['snapshot']['categories'] ?? [], $categoryId);
        if (!$category) { throw new \InvalidArgumentException('问题类目不可用'); }
        $previousCategory = $conversation->category_id;
        $conversation->update(['category_id' => $categoryId, 'priority' => (int) ($category['priority'] ?? 0), 'classification_json' => ['source' => $human ? 'human' : 'user', 'path' => $category['path_names']]]);
        if (($category['force_human'] ?? false) && $conversation->reception_state === 'ai_active') { $this->requestHuman($conversation, 'category_requires_human'); }
        $this->rerouteClassification($conversation, $previousCategory);
    }

    public function appendMember(int $memberId, string $no, string $clientId, string $type, array $content): CustomerServiceMessage
    {
        $settings = $this->settings->toArray();
        if (!$settings['enabled']) { throw new \DomainException('客服服务暂未开启'); }
        if ($clientId === '' || strlen($clientId) > 64) { throw new \InvalidArgumentException('客户端消息标识无效'); }
        $conversation = $this->repository->findMemberConversation($memberId, $no, true) ?? throw new \RuntimeException('客服会话不存在');
        $entity = $this->mapper->fromModel($conversation);
        $entity->assertCanSend('member', $memberId);
        $message = $this->repository->append($conversation, 'member', $memberId, $clientId, $type, $content);
        if (!$message->wasRecentlyCreated) { return $message; }
        if ($type === 'text') {
            $previousCategory = $conversation->category_id;
            $published = $this->configuration->published();
            $source = $conversation->classification_json['source'] ?? '';
            $scopeCategory = in_array($source, ['user', 'human'], true) ? $conversation->category_id : null;
            $identified = $this->keywords->identify($content['text'], $published['snapshot'], $scopeCategory, $source === 'human', (string) $conversation->source);
            if ($identified['conflict']) {
                $conversation->update(['category_id' => null, 'classification_json' => $identified, 'priority' => 0]);
                if ($conversation->reception_state === 'ai_active') { $this->requestHuman($conversation, 'classification_conflict'); }
            }
            if ($identified['category_id']) {
                $category = $this->categories->effective($published['snapshot']['categories'] ?? [], (int) $identified['category_id']);
                $conversation->update(['category_id' => $identified['category_id'], 'classification_json' => $identified + ['path' => $category['path_names'] ?? []], 'priority' => (int) ($category['priority'] ?? 0)]);
                if (($category['force_human'] ?? false) && $conversation->reception_state === 'ai_active') { $this->requestHuman($conversation, 'category_requires_human'); }
            }
            $this->rerouteClassification($conversation, $previousCategory);
            if ($conversation->reception_state === 'ai_active') {
                if (!$settings['ai_enabled']) {
                    $this->requestHuman($conversation, 'ai_disabled');
                } else {
                    $category = $conversation->category_id ? $this->categories->effective($published['snapshot']['categories'] ?? [], $conversation->category_id) : null;
                    $conversation->update(['ai_turn' => (int) $conversation->ai_turn + 1, 'config_revision' => $published['revision'], 'ai_agent_id' => (int) ($category['default_ai_agent_id'] ?? $settings['default_ai_agent_id'])]);
                    if ($conversation->ai_turn > $settings['ai_max_rounds']) { $this->requestHuman($conversation, 'round_limit'); }
                    else { $this->repository->enqueueAi($conversation, $message); }
                }
            }
        } elseif ($conversation->reception_state === 'ai_active') {
            $this->requestHuman($conversation, 'unsupported_message_type');
        }
        return $message;
    }

    /** 未接单时改类目须释放旧预占并重新分配，已接单会话继续由当前人工接待。 */
    private function rerouteClassification(CustomerServiceConversation $conversation, ?int $previousCategory): void
    {
        if ((int) $previousCategory === (int) $conversation->category_id) { return; }
        if ($conversation->reception_state === 'human_assigned') {
            $previousAgent = $conversation->assigned_agent_id;
            $this->assignment->release($conversation);
            $entity = $this->mapper->fromModel($conversation);
            $entity->requeue(date('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
            $this->assignment->log($conversation, 'classification_rerouted', 'system', null, ['previous_category_id' => $previousCategory, 'category_id' => $conversation->category_id, 'previous_agent_id' => $previousAgent]);
        }
        if ($conversation->reception_state === 'human_waiting') { $this->assignment->assignWaitingConversation($conversation); }
    }

    public function rate(int $memberId, string $no, int $score, string $comment): void
    {
        if ($score < 1 || $score > 5 || mb_strlen($comment) > 500) { throw new \InvalidArgumentException('评分或评价内容无效'); }
        $conversation = $this->repository->findMemberConversation($memberId, $no, true) ?? throw new \RuntimeException('会话不存在');
        if ($conversation->reception_state !== 'closed') { throw new \DomainException('请在结束会话后评价'); }
        $this->repository->rate($conversation, $memberId, $score, $comment);
    }

    public function closeMember(int $memberId, string $no): CustomerServiceConversation
    {
        $conversation = $this->repository->findMemberConversation($memberId, $no, true) ?? throw new \RuntimeException('客服会话不存在');
        $entity = $this->mapper->fromModel($conversation);
        if ($entity->close($memberId, 'member_closed', date('Y-m-d H:i:s'))) {
            $this->assignment->release($conversation);
            $conversation->update($entity->toArray());
            $this->assignment->log($conversation, 'closed', 'member', $memberId);
        }
        return $conversation;
    }

    public function allowsAi(CustomerServiceConversation $conversation, int $generation, int $turn, bool $enabled): bool
    {
        return $this->mapper->fromModel($conversation)->allowsAi($generation, $turn, $enabled);
    }

    /** 领域层决定状态和消息，应用层在同一事务中记录待投递事件。 */
    public function applyAiAnswer(CustomerServiceConversation $conversation, array $run, \Plugin\CustomerService\Domain\ValueObject\AiAnswerVo $answer): array
    {
        $settings = $this->settings->toArray();
        $current = $this->configuration->published()['snapshot'];
        $agent = array_column($current['ai_agents'] ?? [], null, 'id')[(int) $run['agent_id']] ?? null;
        $connection = $agent ? (array_column($current['ai_connections'] ?? [], null, 'id')[(int) $agent['connection_id']] ?? null) : null;
        $enabled = $settings['enabled'] && $settings['ai_enabled'] && $agent && ($agent['enabled'] ?? true) && $connection && ($connection['enabled'] ?? true);
        if (!$this->allowsAi($conversation, (int) $run['generation'], (int) $run['ai_turn'], (bool) $enabled)) {
            $changed = $conversation->reception_state === 'ai_active' && !$enabled;
            if ($changed) { $this->requestHuman($conversation, 'ai_unavailable'); }
            return ['status' => 'cancelled', 'reason' => 'stale_generation', 'changed' => $changed, 'message' => null];
        }
        $handoff = $answer->action === 'handoff' || ($answer->action === 'clarify' && $conversation->clarification_count >= $settings['ai_max_clarifications']);
        if ($handoff) {
            $reason = $answer->action === 'clarify' ? 'clarification_limit' : ($answer->reason ?: 'not_answerable');
            $this->requestHuman($conversation, $reason);
            return ['status' => 'handed_off', 'reason' => $reason, 'changed' => true, 'message' => null];
        }
        if ($answer->action === 'clarify') { $conversation->update(['clarification_count' => $conversation->clarification_count + 1]); }
        $message = $this->repository->append($conversation, 'ai', (int) $run['agent_id'], 'ai-run-' . $run['id'], 'text', ['text' => mb_substr($answer->text, 0, (int) $settings['max_message_length'])], (int) $run['id'], $answer->evidenceIds);
        return ['status' => 'completed', 'reason' => $answer->reason ?: null, 'changed' => false, 'message' => $message->wasRecentlyCreated ? $message : null];
    }

    public function maintainQueue(CustomerServiceConversation $conversation, \DateTimeImmutable $now): bool
    {
        $settings = $this->settings->toArray();
        $published = $this->configuration->published()['snapshot'];
        $before = $conversation->toArray();
        $entity = $this->mapper->fromModel($conversation);
        $agent = array_column($published['ai_agents'] ?? [], null, 'id')[(int) $conversation->ai_agent_id] ?? null;
        $connection = $agent ? (array_column($published['ai_connections'] ?? [], null, 'id')[(int) $agent['connection_id']] ?? null) : null;
        if ($conversation->reception_state === 'ai_active' && (!$settings['enabled'] || !$settings['ai_enabled'] || !$agent || !($agent['enabled'] ?? true) || !$connection || !($connection['enabled'] ?? true))) {
            $this->requestHuman($conversation, 'ai_disabled');
        } elseif ($conversation->reception_state === 'human_assigned' && $conversation->assignment_expires_at && strtotime((string) $conversation->assignment_expires_at) <= $now->getTimestamp()) {
            $this->assignment->release($conversation);
            $entity->requeue($now->format('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
        } elseif ($conversation->reception_state === 'human_deferred' && $conversation->handoff_reason === 'outside_business_hours' && $this->routing->onDuty($settings['schedule'] ?? [], $now)) {
            $entity->requeue($now->format('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
        }
        if ($conversation->reception_state === 'human_waiting') {
            $category = $conversation->category_id ? $this->categories->effective($published['categories'] ?? [], (int) $conversation->category_id) : null;
            $timeout = (int) ($category['sla_seconds'] ?? $settings['queue_timeout_seconds']);
            if ($conversation->queue_entered_at && strtotime((string) $conversation->queue_entered_at) + $timeout < $now->getTimestamp()) {
                $entity = $this->mapper->fromModel($conversation);
                $entity->defer('queue_timeout', $now->format('Y-m-d H:i:s'));
                $conversation->update($entity->toArray());
            } else { $this->assignment->assignWaitingConversation($conversation); }
        }
        return $before !== $conversation->toArray();
    }

    public function prepareTakeover(CustomerServiceConversation $conversation): void
    {
        if (!in_array($conversation->reception_state, ['ai_active', 'human_deferred', 'human_waiting'], true)) { throw new \DomainException('会话当前不可接管'); }
        if ($conversation->reception_state !== 'human_waiting') {
            $entity = $this->mapper->fromModel($conversation);
            $entity->requestHuman('agent_takeover', date('Y-m-d H:i:s'));
            $conversation->update($entity->toArray());
        }
    }
}
