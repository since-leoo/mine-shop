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

use App\Infrastructure\Error\CustomerServiceError;
use App\Infrastructure\Model\Member\Member;
use Carbon\Carbon;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceFaq;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;

final class AppCustomerServiceQueryService
{
    public function __construct(private readonly \App\Interface\Common\CurrentUser $currentUser, private readonly \Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface $configuration, private readonly \Plugin\CustomerService\Domain\Service\DomainConversationAccessService $access) {}

    public function assertAccessible(string $no): CustomerServiceConversation
    {
        $conversation = CustomerServiceConversation::query()->where('conversation_no', $no)->first() ?? throw CustomerServiceError::SUPPORT_CONVERSATION_NOT_FOUND->internal();
        $agent = CustomerServiceAgent::query()->where('admin_user_id', $this->currentUser->id())->first();
        if ($agent && !$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        $user = $this->currentUser->user();
        $supervisor = $user && ($user->isSuperAdmin() || $user->hasPermission('customer-service:conversation:manage'));
        if (!$this->access->canRead($conversation->toArray(), (int) ($agent?->id ?? 0), $this->configuration->published()['snapshot'], (bool) $supervisor)) { throw CustomerServiceError::CONVERSATION_READ_FORBIDDEN->exception(); }
        return $conversation;
    }

    private function scoped()
    {
        $query = CustomerServiceConversation::query();
        $user = $this->currentUser->user();
        if ($user && ($user->isSuperAdmin() || $user->hasPermission('customer-service:conversation:manage'))) { return $query; }
        $agent = CustomerServiceAgent::query()->where('admin_user_id', $this->currentUser->id())->first();
        if ($agent && !$agent->enabled) { throw CustomerServiceError::AGENT_DISABLED->exception(); }
        $agentId = (int) ($agent?->id ?? 0);
        $snapshot = $this->configuration->published()['snapshot'];
        $scopes = [['ai_agent_id' => null, 'categories' => $this->access->allowedCategories($agentId, $snapshot)]];
        foreach ($snapshot['ai_agents'] ?? [] as $aiAgent) {
            $scopes[] = ['ai_agent_id' => (int) $aiAgent['id'], 'categories' => $this->access->allowedCategories($agentId, $this->access->routingSnapshot(['ai_agent_id' => $aiAgent['id']], $snapshot))];
        }
        return $query->where(function ($q) use ($agentId, $scopes) {
            $q->where('assigned_agent_id', $agentId)->orWhere(function ($q) use ($scopes) {
                $q->whereNull('assigned_agent_id')->where('reception_state', '!=', 'closed')->where(function ($q) use ($scopes) {
                    foreach ($scopes as $scope) {
                        $q->orWhere(function ($q) use ($scope) {
                            $scope['ai_agent_id'] === null ? $q->whereNull('ai_agent_id') : $q->where('ai_agent_id', $scope['ai_agent_id']);
                            $categories = $scope['categories'];
                            if ($categories !== null) { $q->where(function ($q) use ($categories) { $q->whereIn('category_id', $categories); if (in_array(0, $categories, true)) { $q->orWhereNull('category_id'); } }); }
                        });
                    }
                });
            });
        });
    }

    public function aiRuns(): array
    {
        return \Hyperf\DbConnection\Db::table('customer_service_ai_runs')->orderByDesc('id')->limit(100)->get()->map(static fn ($row) => (array) $row)->all();
    }

    public function queue(): array
    {
        return $this->scoped()->whereIn('reception_state', ['human_waiting', 'human_assigned', 'human_deferred'])->oldest('id')->limit(200)->get()
            ->map(fn (CustomerServiceConversation $conversation) => $this->conversation($conversation))->all();
    }

    public function conversations(array $filters, int $page, int $pageSize): array
    {
        $query = $this->scoped()->orderByDesc('last_message_at')->orderByDesc('id');
        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);
        return ['list' => array_map(fn (CustomerServiceConversation $conversation) => $this->conversation($conversation), $paginator->items()), 'total' => $paginator->total()];
    }

    public function messages(string $conversationNo, ?int $afterSequence = null, int $limit = 100): array
    {
        $conversation = $this->assertAccessible($conversationNo);
        $query = CustomerServiceMessage::query()->where('conversation_id', $conversation->id)->where('visibility', 'public');
        $messages = $afterSequence !== null
            ? $query->where('sequence', '>', $afterSequence)->orderBy('sequence')->limit(max(1, min($limit, 100)))->get()
            : $query->latest('id')->limit(max(1, min($limit, 100)))->get()->reverse()->values();
        return $messages->map(fn (CustomerServiceMessage $message) => $this->formatModelMessage($message))->all();
    }

    public function agents(): array
    {
        return CustomerServiceAgent::query()->orderBy('status')->orderBy('id')->get()->map(static fn (CustomerServiceAgent $agent) => [
            'id' => $agent->id, 'name' => $agent->display_name, 'avatar' => $agent->avatar, 'status' => $agent->status,
            'enabled' => $agent->enabled, 'priority' => $agent->priority, 'weight' => $agent->weight, 'schedule_json' => $agent->schedule_json, 'active_conversation_count' => $agent->current_conversations, 'max_conversations' => $agent->max_conversations,
        ])->all();
    }

    public function agentByAdminUserId(int $adminUserId): ?array
    {
        $agent = CustomerServiceAgent::query()->where('admin_user_id', $adminUserId)->first();
        return $agent === null ? null : [
            'id' => $agent->id, 'name' => $agent->display_name, 'avatar' => $agent->avatar, 'status' => $agent->status,
            'enabled' => $agent->enabled, 'priority' => $agent->priority, 'weight' => $agent->weight, 'schedule_json' => $agent->schedule_json, 'active_conversation_count' => $agent->current_conversations, 'max_conversations' => $agent->max_conversations,
        ];
    }

    public function statistics(): array
    {
        return ['queued_count' => $this->scoped()->whereIn('reception_state', ['human_waiting', 'human_assigned'])->count(), 'active_count' => $this->scoped()->where('reception_state', 'human_active')->count(), 'today_closed_count' => $this->scoped()->where('status', 'closed')->whereDate('closed_at', Carbon::today())->count()];
    }

    public function faqs(int $page, int $pageSize): array
    {
        $paginator = CustomerServiceFaq::query()->orderBy('sort')->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);
        return ['list' => $paginator->items(), 'total' => $paginator->total()];
    }

    public function formatMessage(array $message): array
    {
        $content = \is_array($message['content_json'] ?? null) ? $message['content_json'] : [];
        return $this->formatMessageData($message, $content);
    }

    private function conversation(CustomerServiceConversation $conversation): array
    {
        $member = Member::query()->find($conversation->member_id);
        $agent = $conversation->assigned_agent_id ? CustomerServiceAgent::query()->find($conversation->assigned_agent_id) : null;
        return [
            'id' => $conversation->id, 'no' => $conversation->conversation_no,
            'status' => \in_array($conversation->status, ['waiting', 'assigned'], true) ? 'queued' : $conversation->status,
            'member_id' => $conversation->member_id, 'member_name' => $member?->nickname ?: '会员 #' . $conversation->member_id,
            'member_avatar' => $member?->avatar, 'agent_name' => $agent?->display_name,
            'reception_state' => $conversation->reception_state, 'category_id' => $conversation->category_id, 'classification' => $conversation->classification_json, 'handoff_reason' => $conversation->handoff_reason, 'generation' => $conversation->generation, 'assigned_agent_id' => $conversation->assigned_agent_id, 'source' => $conversation->source, 'last_message_at' => $conversation->last_message_at?->toDateTimeString(),
        ];
    }

    private function formatModelMessage(CustomerServiceMessage $message): array
    {
        $content = $message->content_json;
        return $this->formatMessageData($message->toArray(), $content);
    }

    private function formatMessageData(array $message, array $content): array
    {
        $type = ($message['message_type'] ?? '') === 'product' ? 'product_card' : (string) ($message['message_type'] ?? 'text');
        return [
            'sequence' => (int) ($message['sequence'] ?? 0), 'client_message_id' => $message['client_message_id'] ?? null, 'evidence' => $message['evidence_json'] ?? [], 'id' => (int) ($message['id'] ?? 0), 'type' => $type, 'sender_type' => (string) ($message['sender_type'] ?? 'system'),
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
