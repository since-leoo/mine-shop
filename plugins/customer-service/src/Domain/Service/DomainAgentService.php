<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use App\Infrastructure\Error\CustomerServiceError;
use Plugin\CustomerService\Domain\Mapper\CustomerServiceConversationMapper;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;
use Plugin\CustomerService\Service\CustomerServiceAssignmentService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class DomainAgentService
{
    public function __construct(private readonly CustomerServiceConversationRepository $repository, private readonly CustomerServiceConversationMapper $mapper, private readonly CustomerServiceAssignmentService $assignment, private readonly CustomerServiceSettingsResolver $settings, private readonly DomainConfigurationService $configuration, private readonly \Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface $definitions, private readonly DomainConversationAccessService $access) {}

    public function agent(int $adminUserId, string $name, ?string $avatar = null): CustomerServiceAgent
    {
        return CustomerServiceAgent::query()->firstOrCreate(['admin_user_id' => $adminUserId], ['display_name' => $name, 'avatar' => $avatar, 'status' => 'offline', 'max_conversations' => (int) $this->settings->toArray()['max_conversations_per_agent'], 'enabled' => true, 'priority' => 0, 'weight' => 1, 'current_conversations' => 0]);
    }

    public function updateStatus(int $adminUserId, string $name, string $status, ?int $requestedAgentId = null): CustomerServiceAgent
    {
        if (!in_array($status, ['online', 'busy', 'away', 'offline'], true)) { throw CustomerServiceError::AGENT_STATUS_INVALID->invalid(); }
        $registered = $this->agent($adminUserId, $name);
        $agent = CustomerServiceAgent::query()->whereKey($registered->id)->lockForUpdate()->first();
        if ($requestedAgentId !== null && (int) $agent->id !== $requestedAgentId) { throw CustomerServiceError::AGENT_STATUS_UPDATE_FORBIDDEN->exception(); }
        if (!$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        $agent->update(['status' => $status, 'last_heartbeat_at' => date('Y-m-d H:i:s')]);
        return $agent;
    }

    public function accept(int $adminUserId, string $name, string $no): CustomerServiceConversation
    {
        $conversation = $this->repository->lock($no);
        $registered = $this->agent($adminUserId, $name);
        $agent = CustomerServiceAgent::query()->whereKey($registered->id)->lockForUpdate()->first();
        if (!$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        if (!$this->access->canRead($conversation->toArray(), (int) $agent->id, $this->definitions->published()['snapshot'])) { throw CustomerServiceError::CONVERSATION_SKILL_MISMATCH->exception(); }
        $categories = $this->access->allowedCategories((int) $agent->id, $this->access->routingSnapshot($conversation->toArray(), $this->definitions->published()['snapshot']));
        if ($categories !== null && !in_array((int) $conversation->category_id, $categories, true)) { throw CustomerServiceError::AGENT_SKILL_UNAVAILABLE->exception(); }
        $entity = $this->mapper->fromModel($conversation);
        $newReservation = $entity->accept((int) $agent->id);
        if ($newReservation && $agent->current_conversations >= $agent->max_conversations) { throw CustomerServiceError::AGENT_CAPACITY_REACHED->exception(); }
        if ($newReservation) { $agent->update(['current_conversations' => $agent->current_conversations + 1]); }
        $agent->update(['status' => 'online', 'last_heartbeat_at' => date('Y-m-d H:i:s')]);
        $conversation->update($entity->toArray());
        $this->assignment->recordAgent($conversation, (int) $agent->id, 'accepted', $adminUserId);
        $this->assignment->log($conversation, 'accepted', 'agent', $adminUserId);
        return $conversation;
    }

    public function transfer(int $adminUserId, string $name, string $no, int $targetId): CustomerServiceConversation
    {
        $conversation = $this->repository->lock($no);
        $current = $this->agent($adminUserId, $name);
        if (!$current->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        $agents = CustomerServiceAgent::query()->whereIn('id', [(int) $current->id, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $target = $agents->get($targetId) ?? throw CustomerServiceError::TARGET_AGENT_NOT_FOUND->internal();
        if (!$target->enabled || $target->status !== 'online' || $target->current_conversations >= $target->max_conversations || !$target->last_heartbeat_at || strtotime((string) $target->last_heartbeat_at) < time() - 90) { throw CustomerServiceError::TARGET_AGENT_UNAVAILABLE->exception(); }
        $entity = $this->mapper->fromModel($conversation);
        $entity->transfer((int) $current->id, $targetId);
        $this->assignment->release($conversation);
        $target->update(['current_conversations' => $target->current_conversations + 1]);
        $conversation->update($entity->toArray());
        $this->assignment->recordAgent($conversation, $targetId, 'transferred_in', $adminUserId);
        $this->assignment->log($conversation, 'transferred', 'agent', $adminUserId, ['from_agent_id' => $current->id, 'to_agent_id' => $targetId]);
        return $conversation;
    }

    public function close(int $adminUserId, string $name, string $no): CustomerServiceConversation
    {
        $conversation = $this->repository->lock($no);
        $agent = $this->agent($adminUserId, $name);
        if (!$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        if ((int) $conversation->assigned_agent_id !== (int) $agent->id) { throw CustomerServiceError::CONVERSATION_CLOSE_FORBIDDEN->exception(); }
        $entity = $this->mapper->fromModel($conversation);
        if ($entity->close($adminUserId, 'agent_closed', date('Y-m-d H:i:s'))) {
            $this->assignment->release($conversation);
            $conversation->update($entity->toArray());
            $this->assignment->log($conversation, 'closed', 'agent', $adminUserId);
        }
        return $conversation;
    }

    public function sendText(int $agentId, string $no, string $clientId, string $text): CustomerServiceMessage
    {
        $settings = $this->settings->toArray();
        if (!$settings['enabled']) { throw CustomerServiceError::SERVICE_DISABLED->exception(); }
        if ($clientId === '' || strlen($clientId) > 64 || trim($text) === '' || mb_strlen($text) > $settings['max_message_length']) { throw CustomerServiceError::MESSAGE_CONTENT_OR_ID_INVALID->invalid(); }
        $conversation = $this->repository->lock($no);
        $agent = CustomerServiceAgent::query()->whereKey($agentId)->first();
        if (!$agent || !$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        $this->mapper->fromModel($conversation)->assertCanSend('agent', $agentId);
        return $this->repository->append($conversation, 'agent', $agentId, $clientId, 'text', ['text' => trim($text)]);
    }

    public function configure(int $agentId, array $input): CustomerServiceAgent
    {
        $agent = CustomerServiceAgent::query()->whereKey($agentId)->lockForUpdate()->first() ?? throw CustomerServiceError::AGENT_NOT_FOUND->internal();
        $values = array_intersect_key($input, array_flip(['display_name', 'enabled', 'priority', 'weight', 'max_conversations', 'schedule_json']));
        if (isset($values['max_conversations']) && ((int) $values['max_conversations'] < 1 || (int) $values['max_conversations'] > 1000 || (int) $values['max_conversations'] < $agent->current_conversations)) { throw CustomerServiceError::CAPACITY_INVALID->invalid(); }
        if (isset($values['weight']) && ((int) $values['weight'] < 1 || (int) $values['weight'] > 100)) { throw CustomerServiceError::WEIGHT_INVALID->invalid(); }
        $this->configuration->validateSchedule($values['schedule_json'] ?? []);
        $agent->update($values);
        return $agent;
    }
}
