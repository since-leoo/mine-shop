<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Repository;

use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;

final class CustomerServiceConversationRepository
{
    // 会员记录天然存在，用它串行化首次建会话，防止并发请求各自创建活动会话。
    public function lockMember(int $memberId): void
    {
        if (!Db::table('members')->where('id', $memberId)->lockForUpdate()->first()) { throw new \RuntimeException('会员不存在'); }
    }

    public function findOpenByMember(int $memberId): ?CustomerServiceConversation
    {
        return CustomerServiceConversation::query()->where('member_id', $memberId)->where('reception_state', '!=', 'closed')->latest('id')->lockForUpdate()->first();
    }

    public function findMemberConversation(int $memberId, string $conversationNo, bool $lock = false): ?CustomerServiceConversation
    {
        $query = CustomerServiceConversation::query()->where('member_id', $memberId)->where('conversation_no', $conversationNo);
        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    public function lock(string $conversationNo): CustomerServiceConversation
    {
        return CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->lockForUpdate()->first() ?? throw new \RuntimeException('客服会话不存在');
    }

    public function createConversation(int $memberId, string $source, ?string $subject, array $attributes = []): CustomerServiceConversation
    {
        return CustomerServiceConversation::query()->create(array_replace([
            'conversation_no' => 'CS' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4))),
            'member_id' => $memberId, 'status' => 'waiting', 'reception_state' => 'human_waiting',
            'source' => $source, 'subject' => $subject, 'generation' => 1, 'last_sequence' => 0,
        ], $attributes));
    }

    public function messages(CustomerServiceConversation $conversation, ?int $beforeId, int $limit, ?int $afterSequence = null): array
    {
        $query = CustomerServiceMessage::query()->where('conversation_id', $conversation->id)->where('visibility', 'public');
        if ($afterSequence !== null) {
            return $query->where('sequence', '>', $afterSequence)->orderBy('sequence')->limit($limit)->get()->all();
        }
        return $query->when($beforeId, static fn ($q) => $q->where('id', '<', $beforeId))->latest('id')->limit($limit)->get()->reverse()->values()->all();
    }

    /** Caller locks the conversation inside its transaction. */
    public function append(CustomerServiceConversation $conversation, string $senderType, int $senderId, string $clientId, string $type, array $content, ?int $aiRunId = null, array $evidence = []): CustomerServiceMessage
    {
        $existing = CustomerServiceMessage::query()->where('conversation_id', $conversation->id)->where('sender_type', $senderType)->where('sender_id', $senderId)->where('client_message_id', $clientId)->first();
        if ($existing) {
            if ($existing->message_type !== $type || $existing->content_json != $content) { throw new \DomainException('消息标识已用于不同内容'); }
            return $existing;
        }
        $message = CustomerServiceMessage::query()->create([
            'conversation_id' => $conversation->id, 'sender_type' => $senderType, 'sender_id' => $senderId,
            'client_message_id' => $clientId, 'message_type' => $type, 'content_json' => $content,
            'sequence' => (int) $conversation->last_sequence + 1, 'sent_at' => date('Y-m-d H:i:s'),
            'ai_run_id' => $aiRunId, 'visibility' => 'public', 'evidence_json' => $evidence ?: null,
        ]);
        $conversation->update(['last_message_id' => $message->id, 'last_message_at' => $message->sent_at, 'last_sequence' => $message->sequence]);
        return $message;
    }

    public function enqueueAi(CustomerServiceConversation $conversation, CustomerServiceMessage $message): void
    {
        Db::table('customer_service_ai_runs')->insertOrIgnore([
            'conversation_id' => $conversation->id, 'trigger_message_id' => $message->id,
            'generation' => $conversation->generation, 'ai_turn' => $conversation->ai_turn,
            'agent_id' => $conversation->ai_agent_id, 'config_revision' => $conversation->config_revision,
            'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    public function rate(CustomerServiceConversation $conversation, int $memberId, int $score, string $comment): void
    {
        Db::table('customer_service_satisfaction_ratings')->updateOrInsert(['conversation_id' => $conversation->id, 'member_id' => $memberId], ['score' => $score, 'comment' => $comment, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
