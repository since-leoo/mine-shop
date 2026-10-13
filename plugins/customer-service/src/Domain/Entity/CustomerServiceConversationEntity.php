<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Entity;

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
            throw new \DomainException('当前会话不能重新转人工');
        }
        if ($this->state() === 'human_waiting') { return; }
        $this->change(['reception_state' => 'human_waiting', 'status' => 'waiting', 'generation' => $this->generation() + 1, 'queue_entered_at' => $now, 'handoff_reason' => $reason, 'assigned_agent_id' => null]);
    }

    public function assign(int $agentId, string $expires): void
    {
        if ($this->state() !== 'human_waiting' || $this->value('assigned_agent_id')) { throw new \DomainException('会话不可分配'); }
        $this->change(['reception_state' => 'human_assigned', 'status' => 'assigned', 'assigned_agent_id' => $agentId, 'assignment_expires_at' => $expires]);
    }

    public function accept(int $agentId): bool
    {
        if (!in_array($this->state(), ['human_waiting', 'human_assigned'], true)) { throw new \DomainException('会话不可接待'); }
        if ($this->value('assigned_agent_id') && (int) $this->value('assigned_agent_id') !== $agentId) { throw new \DomainException('会话已分配其他客服'); }
        $reserve = $this->state() === 'human_waiting';
        $this->change(['reception_state' => 'human_active', 'status' => 'active', 'assigned_agent_id' => $agentId, 'assignment_expires_at' => null, 'generation' => $this->generation() + 1]);
        return $reserve;
    }

    public function transfer(int $from, int $to): void
    {
        if ($this->state() !== 'human_active' || (int) $this->value('assigned_agent_id') !== $from || $from === $to) { throw new \DomainException('无权转接或目标坐席无效'); }
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
        if (!in_array($this->state(), ['human_waiting', 'human_assigned'], true)) { throw new \DomainException('当前会话不可转留言'); }
        $this->change(['reception_state' => 'human_deferred', 'status' => 'waiting', 'assigned_agent_id' => null, 'assignment_expires_at' => null, 'deferred_at' => $now, 'handoff_reason' => $reason, 'generation' => $this->generation() + 1]);
    }

    public function requeue(string $now): void
    {
        if (!in_array($this->state(), ['human_assigned', 'human_deferred'], true)) { return; }
        $this->change(['reception_state' => 'human_waiting', 'status' => 'waiting', 'assigned_agent_id' => null, 'assignment_expires_at' => null, 'queue_entered_at' => $now, 'generation' => $this->generation() + 1]);
    }

    public function assertCanSend(string $type, int $senderId): void
    {
        if ($this->state() === 'closed') { throw new \DomainException('会话已关闭'); }
        if ($type === 'member' && (int) $this->value('member_id') !== $senderId) { throw new \DomainException('无权访问会话'); }
        if ($type === 'agent' && ($this->state() !== 'human_active' || (int) $this->value('assigned_agent_id') !== $senderId)) { throw new \DomainException('无权在该会话回复'); }
    }

    public function allowsAi(int $generation, int $turn, bool $enabled): bool
    {
        return $enabled && $this->state() === 'ai_active' && $this->generation() === $generation && (int) $this->value('ai_turn', 0) === $turn;
    }

    private function change(array $values): void { $this->data = array_replace($this->data, $values); $this->dirty = array_replace($this->dirty, $values); }
}
