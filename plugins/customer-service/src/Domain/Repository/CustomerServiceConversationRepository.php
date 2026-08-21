<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\CustomerService\Domain\Repository;

use Carbon\Carbon;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;

final class CustomerServiceConversationRepository
{
    public function findOpenByMember(int $memberId): ?CustomerServiceConversation
    {
        return CustomerServiceConversation::query()
            ->where('member_id', $memberId)
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->latest('id')
            ->first();
    }

    public function findMemberConversation(int $memberId, string $conversationNo): ?CustomerServiceConversation
    {
        return CustomerServiceConversation::query()->where('member_id', $memberId)->where('conversation_no', $conversationNo)->first();
    }

    public function createConversation(int $memberId, string $source, ?string $subject): CustomerServiceConversation
    {
        return CustomerServiceConversation::query()->create([
            'conversation_no' => 'CS' . date('YmdHis') . mb_strtoupper(bin2hex(random_bytes(4))),
            'member_id' => $memberId,
            'status' => 'waiting',
            'source' => $source,
            'subject' => $subject,
        ]);
    }

    public function messages(CustomerServiceConversation $conversation, ?int $beforeId, int $limit): array
    {
        return CustomerServiceMessage::query()
            ->where('conversation_id', $conversation->id)
            ->when($beforeId, static fn ($query) => $query->where('id', '<', $beforeId))
            ->latest('id')->limit($limit)->get()->reverse()->values()->all();
    }

    public function appendMessage(CustomerServiceConversation $conversation, int $memberId, string $clientMessageId, string $type, array $content): CustomerServiceMessage
    {
        $message = CustomerServiceMessage::query()->firstOrCreate(
            ['conversation_id' => $conversation->id, 'client_message_id' => $clientMessageId],
            [
                'sender_type' => 'member', 'sender_id' => $memberId, 'message_type' => $type,
                'content_json' => $content, 'sent_at' => Carbon::now(),
            ],
        );
        $conversation->update(['last_message_id' => $message->id, 'last_message_at' => $message->sent_at]);
        return $message;
    }
}
