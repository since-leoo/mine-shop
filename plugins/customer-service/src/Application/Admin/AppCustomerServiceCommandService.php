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

namespace Plugin\CustomerService\Application\Admin;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;
use Plugin\CustomerService\Service\CustomerServiceAssignmentService;

final class AppCustomerServiceCommandService
{
    public function __construct(private readonly CustomerServiceAssignmentService $assignment) {}

    public function agent(int $adminUserId, string $name, ?string $avatar = null): CustomerServiceAgent
    {
        return CustomerServiceAgent::query()->firstOrCreate(['admin_user_id' => $adminUserId], ['display_name' => $name, 'avatar' => $avatar, 'status' => 'offline']);
    }

    public function updateAgentStatus(int $adminUserId, string $name, string $status): CustomerServiceAgent
    {
        if (! \in_array($status, ['online', 'busy', 'away', 'offline'], true)) {
            throw new \InvalidArgumentException('坐席状态无效');
        }
        $agent = $this->agent($adminUserId, $name);
        $agent->update(['status' => $status, 'last_heartbeat_at' => Carbon::now()]);
        return $agent->refresh();
    }

    public function accept(int $adminUserId, string $name, string $conversationNo): CustomerServiceConversation
    {
        return Db::transaction(function () use ($adminUserId, $name, $conversationNo) {
            $agent = $this->agent($adminUserId, $name);
            if (! \in_array($agent->status, ['online', 'busy'], true)) {
                throw new \DomainException('当前坐席不可接待');
            }
            $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->lockForUpdate()->first() ?? throw new \RuntimeException('客服会话不存在');
            if (! \in_array($conversation->status, ['waiting', 'assigned'], true)) {
                throw new \DomainException('该会话已被接待或关闭');
            }
            if ($conversation->status === 'assigned' && (int) $conversation->assigned_agent_id !== (int) $agent->id) {
                throw new \DomainException('该会话已分配给其他坐席');
            }
            if ($conversation->status === 'waiting' && $agent->current_conversations >= $agent->max_conversations) {
                throw new \DomainException('已达到最大接待会话数');
            }
            $wasWaiting = $conversation->status === 'waiting';
            $conversation->update(['status' => 'active', 'assigned_agent_id' => $agent->id]);
            if ($wasWaiting) {
                $agent->increment('current_conversations');
            }
            $this->assignment->recordAgent($conversation, (int) $agent->id, 'accepted', $adminUserId);
            $this->assignment->log($conversation, 'accepted', 'agent', $adminUserId, ['agent_id' => $agent->id]);
            return $conversation->refresh();
        });
    }

    public function transfer(int $adminUserId, string $name, string $conversationNo, int $targetAgentId): void
    {
        Db::transaction(function () use ($adminUserId, $name, $conversationNo, $targetAgentId) {
            $current = $this->agent($adminUserId, $name);
            $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->lockForUpdate()->first() ?? throw new \RuntimeException('客服会话不存在');
            if ($conversation->assigned_agent_id !== $current->id || $conversation->status !== 'active') {
                throw new \DomainException('无权转接该会话');
            }
            $target = CustomerServiceAgent::query()->lockForUpdate()->find($targetAgentId) ?? throw new \RuntimeException('目标坐席不存在');
            if ($target->status !== 'online' || $target->current_conversations >= $target->max_conversations) {
                throw new \DomainException('目标坐席当前不可接待');
            }
            $conversation->update(['assigned_agent_id' => $target->id, 'status' => 'active']);
            $current->decrement('current_conversations');
            $target->increment('current_conversations');
            $this->assignment->recordAgent($conversation, (int) $current->id, 'transferred_out', $adminUserId);
            $this->assignment->recordAgent($conversation, (int) $target->id, 'transferred_in', $adminUserId);
            $this->assignment->log($conversation, 'transferred', 'agent', $adminUserId, ['from_agent_id' => $current->id, 'to_agent_id' => $target->id]);
        });
    }

    public function close(int $adminUserId, string $name, string $conversationNo): void
    {
        Db::transaction(function () use ($adminUserId, $name, $conversationNo) {
            $agent = $this->agent($adminUserId, $name);
            $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->lockForUpdate()->first() ?? throw new \RuntimeException('客服会话不存在');
            if ($conversation->assigned_agent_id !== $agent->id || $conversation->status !== 'active') {
                throw new \DomainException('无权关闭该会话');
            }
            $conversation->update(['status' => 'closed', 'closed_by' => $adminUserId, 'closed_reason' => 'agent_closed', 'closed_at' => Carbon::now()]);
            $agent->decrement('current_conversations');
            $this->assignment->recordAgent($conversation, (int) $agent->id, 'closed', $adminUserId);
            $this->assignment->log($conversation, 'closed', 'agent', $adminUserId, ['reason' => 'agent_closed']);
        });
    }

    public function sendText(int $agentId, string $conversationNo, string $clientMessageId, string $text): array
    {
        $text = trim($text);
        if ($clientMessageId === '' || mb_strlen($clientMessageId) > 64 || $text === '' || mb_strlen($text) > 1000) {
            throw new \InvalidArgumentException('消息内容或客户端消息标识无效');
        }
        $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->first() ?? throw new \RuntimeException('客服会话不存在');
        if ((int) $conversation->assigned_agent_id !== $agentId || $conversation->status !== 'active') {
            throw new \DomainException('无权在该会话发送消息');
        }
        $message = CustomerServiceMessage::query()->firstOrCreate(
            ['conversation_id' => $conversation->id, 'client_message_id' => $clientMessageId],
            ['sender_type' => 'agent', 'sender_id' => $agentId, 'message_type' => 'text', 'content_json' => ['text' => $text], 'sent_at' => Carbon::now()],
        );
        $conversation->update(['last_message_id' => $message->id, 'last_message_at' => $message->sent_at]);
        return $message->toArray();
    }

    public function saveFaq(?int $id, array $payload): CustomerServiceFaq
    {
        if (trim((string) ($payload['question'] ?? '')) === '' || trim((string) ($payload['answer'] ?? '')) === '') {
            throw new \InvalidArgumentException('问题和答案不能为空');
        }
        $faq = $id ? CustomerServiceFaq::query()->find($id) ?? throw new \RuntimeException('常见问题不存在') : new CustomerServiceFaq();
        $faq->fill(['question' => trim($payload['question']), 'answer' => trim($payload['answer']), 'category_name' => trim((string) ($payload['category_name'] ?? '')) ?: null, 'sort' => (int) ($payload['sort'] ?? 0), 'enabled' => (bool) ($payload['enabled'] ?? true)]);
        $faq->save();
        return $faq;
    }
}
