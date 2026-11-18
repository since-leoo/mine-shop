<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Entity;

use App\Infrastructure\Error\CustomerServiceError;

final class CustomerServiceConversationEntity
{
    private array $dirty = [];
    public function __construct(private array $data) {}
    public function state(): string { return $this->data['reception_state']; }
    public function generation(): int { return (int) $this->data['generation']; }
    public function value(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
    public function toArray(): array { return $this->dirty; }

    public function requestHuman(string $reason, string $now): void
    {
        if (in_array($this->state(), ['closed', 'human_active', 'human_assigned'], true)) {
            throw CustomerServiceError::HANDOFF_STATE_INVALID->exception();
        }
        if ($this->state() === 'human_waiting') { return; }
        $this->change(['reception_state' => 'human_waiting', 'status' => 'waiting', 'generation' => $this->generation() + 1, 'queue_entered_at' => $now, 'handoff_reason' => $reason, 'assigned_agent_id' => null]);
    }

    public function assign(int $agentId, string $expires): void
    {
        if ($this->state() !== 'human_waiting' || $this->value('assigned_agent_id')) { throw CustomerServiceError::ASSIGNMENT_STATE_INVALID->exception(); }
        $this->change(['reception_state' => 'human_assigned', 'status' => 'assigned', 'assigned_agent_id' => $agentId, 'assignment_expires_at' => $expires]);
    }

    public function accept(int $agentId): bool
    {
        if (!in_array($this->state(), ['human_waiting', 'human_assigned'], true)) { throw CustomerServiceError::ACCEPTANCE_STATE_INVALID->exception(); }
        if ($this->value('assigned_agent_id') && (int) $this->value('assigned_agent_id') !== $agentId) { throw CustomerServiceError::CONVERSATION_ASSIGNED_ELSEWHERE->exception(); }
        $reserve = $this->state() === 'human_waiting';
        $this->change(['reception_state' => 'human_active', 'status' => 'active', 'assigned_agent_id' => $agentId, 'assignment_expires_at' => null, 'generation' => $this->generation() + 1]);
        return $reserve;
    }

    public function transfer(int $from, int $to): void
    {
        if ($this->state() !== 'human_active' || (int) $this->value('assigned_agent_id') !== $from || $from === $to) { throw CustomerServiceError::TRANSFER_FORBIDDEN->exception(); }
        $this->change(['assigned_agent_id' => $to, 'generation' => $this->generation() + 1]);
    }

    public function close(int $operatorId, string $reason, string $now): bool
    {
        if ($this->state() === 'closed') { return false; }
        $this->change(['reception_state' => 'closed', 'status' => 'closed', 'generation' => $this->generation() + 1, 'closed_by' => $operatorId, 'closed_reason' => $reason, 'closed_at' => $now, 'assignment_expires_at' => null]);
        return true;
    }

    public function defer(string $reason, string $now): void
    {
        if (!in_array($this->state(), ['human_waiting', 'human_assigned'], true)) { throw CustomerServiceError::DEFER_STATE_INVALID->exception(); }
        $this->change(['reception_state' => 'human_deferred', 'status' => 'waiting', 'assigned_agent_id' => null, 'assignment_expires_at' => null, 'deferred_at' => $now, 'handoff_reason' => $reason, 'generation' => $this->generation() + 1]);
    }

    public function requeue(string $now): void
    {
        if (!in_array($this->state(), ['human_assigned', 'human_deferred'], true)) { return; }
        $this->change(['reception_state' => 'human_waiting', 'status' => 'waiting', 'assigned_agent_id' => null, 'assignment_expires_at' => null, 'queue_entered_at' => $now, 'generation' => $this->generation() + 1]);
    }

    public function assertCanSend(string $type, int $senderId): void
    {
        if ($this->state() === 'closed') { throw CustomerServiceError::CONVERSATION_CLOSED->exception(); }
        if ($type === 'member' && (int) $this->value('member_id') !== $senderId) { throw CustomerServiceError::CONVERSATION_ACCESS_FORBIDDEN->exception(); }
        if ($type === 'agent' && ($this->state() !== 'human_active' || (int) $this->value('assigned_agent_id') !== $senderId)) { throw CustomerServiceError::CONVERSATION_REPLY_FORBIDDEN->exception(); }
    }

    public function allowsAi(int $generation, int $turn, bool $enabled): bool
    {
        return $enabled && $this->state() === 'ai_active' && $this->generation() === $generation && (int) $this->value('ai_turn', 0) === $turn;
    }

    private function change(array $values): void { $this->data = array_replace($this->data, $values); $this->dirty = array_replace($this->dirty, $values); }
}
