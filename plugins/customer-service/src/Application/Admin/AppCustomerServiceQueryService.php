<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Admin;

use Carbon\Carbon;
use App\Infrastructure\Model\Member\Member;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;

final class AppCustomerServiceQueryService
{
    public function queue(): array
    {
        return CustomerServiceConversation::query()->whereIn('status', ['waiting', 'assigned'])->oldest('id')->get()
            ->map(fn (CustomerServiceConversation $conversation) => $this->conversation($conversation))->all();
    }

    public function conversations(array $filters, int $page, int $pageSize): array
    {
        $query = CustomerServiceConversation::query()->orderByDesc('last_message_at')->orderByDesc('id');
        if (($filters['status'] ?? '') !== '') $query->where('status', $filters['status']);
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);
        return ['list' => array_map(fn (CustomerServiceConversation $conversation) => $this->conversation($conversation), $paginator->items()), 'total' => $paginator->total()];
    }

    public function messages(string $conversationNo): array
    {
        $conversation = CustomerServiceConversation::query()->where('conversation_no', $conversationNo)->first() ?? throw new \RuntimeException('客服会话不存在');
        return CustomerServiceMessage::query()->where('conversation_id', $conversation->id)->oldest('id')->get()
            ->map(fn (CustomerServiceMessage $message) => $this->formatModelMessage($message))->all();
    }

    public function agents(): array
    {
        return CustomerServiceAgent::query()->orderBy('status')->orderBy('id')->get()->map(static fn (CustomerServiceAgent $agent) => [
            'id' => $agent->id, 'name' => $agent->display_name, 'avatar' => $agent->avatar, 'status' => $agent->status,
            'active_conversation_count' => $agent->current_conversations, 'max_conversations' => $agent->max_conversations,
        ])->all();
    }

    public function agentByAdminUserId(int $adminUserId): ?array
    {
        $agent = CustomerServiceAgent::query()->where('admin_user_id', $adminUserId)->first();
        return $agent === null ? null : [
            'id' => $agent->id, 'name' => $agent->display_name, 'avatar' => $agent->avatar, 'status' => $agent->status,
            'active_conversation_count' => $agent->current_conversations, 'max_conversations' => $agent->max_conversations,
        ];
    }

    public function statistics(): array
    {
        return ['queued_count' => CustomerServiceConversation::query()->whereIn('status', ['waiting', 'assigned'])->count(), 'active_count' => CustomerServiceConversation::query()->where('status', 'active')->count(), 'today_closed_count' => CustomerServiceConversation::query()->where('status', 'closed')->whereDate('closed_at', Carbon::today())->count()];
    }

    public function faqs(int $page, int $pageSize): array
    {
        $paginator = CustomerServiceFaq::query()->orderBy('sort')->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);
        return ['list' => $paginator->items(), 'total' => $paginator->total()];
    }

    public function formatMessage(array $message): array
    {
        $content = is_array($message['content_json'] ?? null) ? $message['content_json'] : [];
        return $this->formatMessageData($message, $content);
    }

    private function conversation(CustomerServiceConversation $conversation): array
    {
        $member = Member::query()->find($conversation->member_id);
        $agent = $conversation->assigned_agent_id ? CustomerServiceAgent::query()->find($conversation->assigned_agent_id) : null;
        return [
            'id' => $conversation->id, 'no' => $conversation->conversation_no,
            'status' => in_array($conversation->status, ['waiting', 'assigned'], true) ? 'queued' : $conversation->status,
            'member_id' => $conversation->member_id, 'member_name' => $member?->nickname ?: '会员 #' . $conversation->member_id,
            'member_avatar' => $member?->avatar, 'agent_name' => $agent?->display_name,
            'source' => $conversation->source, 'last_message_at' => $conversation->last_message_at?->toDateTimeString(),
        ];
    }

    private function formatModelMessage(CustomerServiceMessage $message): array
    {
        $content = is_array($message->content_json) ? $message->content_json : [];
        return $this->formatMessageData($message->toArray(), $content);
    }

    private function formatMessageData(array $message, array $content): array
    {
        $type = ($message['message_type'] ?? '') === 'product' ? 'product_card' : (string) ($message['message_type'] ?? 'text');
        return [
            'id' => (int) ($message['id'] ?? 0), 'type' => $type, 'sender_type' => (string) ($message['sender_type'] ?? 'system'),
            'content' => (string) ($content['text'] ?? $content['answer'] ?? $content['title'] ?? ''),
            'created_at' => (string) ($message['sent_at'] ?? ''),
            'extra' => [
                'image_url' => $content['url'] ?? null, 'product_id' => $content['product_id'] ?? null,
                'product_name' => $content['title'] ?? null, 'product_image' => $content['image'] ?? null,
                'product_price' => $content['price'] ?? null,
            ],
        ];
    }
}
