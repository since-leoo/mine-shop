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

namespace App\Domain\Infrastructure\SystemMessage\Repository;

use App\Domain\Infrastructure\SystemMessage\Enum\MessageStatus;
use App\Infrastructure\Model\SystemMessage\Message;
use App\Infrastructure\Model\SystemMessage\UserMessage;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;

class MessageRepository
{
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function findById(int $id): ?Message
    {
        return Message::find($id);
    }

    public function list(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = Message::query();
        $this->applyFilters($query, $filters);
        $total = $query->count();
        $messages = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        return [
            'data' => $messages, 'total' => $total, 'page' => $page,
            'page_size' => $pageSize, 'total_pages' => ceil($total / $pageSize),
        ];
    }

    public function getUserMessages(int $userId, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = UserMessage::with(['message'])
            ->where('user_id', $userId)
            ->where('is_deleted', false);
        $this->applyUserMessageFilters($query, $filters);
        $total = $query->count();
        $messages = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        return [
            'data' => $messages, 'total' => $total, 'page' => $page,
            'page_size' => $pageSize, 'total_pages' => ceil($total / $pageSize),
        ];
    }

    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = Message::query();
        $query->where(static function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('content', 'like', "%{$keyword}%");
        });
        $this->applyFilters($query, $filters);
        $total = $query->count();
        $messages = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        return [
            'data' => $messages, 'total' => $total, 'page' => $page,
            'page_size' => $pageSize, 'total_pages' => ceil($total / $pageSize),
            'keyword' => $keyword,
        ];
    }

    public function getStatistics(): array
    {
        $total = Message::count();
        $sent = Message::where('status', MessageStatus::SENT->value)->count();
        $draft = Message::where('status', MessageStatus::DRAFT->value)->count();
        $scheduled = Message::where('status', MessageStatus::SCHEDULED->value)->count();
        $failed = Message::where('status', MessageStatus::FAILED->value)->count();
        $byType = Message::selectRaw('type, COUNT(*) as count')->groupBy('type')->pluck('count', 'type')->toArray();
        $recentMessages = Message::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->pluck('count', 'date')->toArray();
        $totalUserMessages = UserMessage::count();
        $readMessages = UserMessage::where('is_read', true)->count();
        $unreadMessages = UserMessage::where('is_read', false)->where('is_deleted', false)->count();
        return [
            'messages' => [
                'total' => $total, 'sent' => $sent, 'draft' => $draft,
                'scheduled' => $scheduled, 'failed' => $failed,
                'by_type' => $byType, 'recent' => $recentMessages,
            ],
            'user_messages' => [
                'total' => $totalUserMessages, 'read' => $readMessages, 'unread' => $unreadMessages,
                'read_rate' => $totalUserMessages > 0 ? round(($readMessages / $totalUserMessages) * 100, 2) : 0,
            ],
        ];
    }

    public function getPopularMessages(int $limit = 10): Collection
    {
        return Message::withCount('userMessages')
            ->where('status', MessageStatus::SENT->value)
            ->orderBy('user_messages_count', 'desc')
            ->limit($limit)->get();
    }

    public function getRecentMessages(int $days = 7, int $limit = 20): Collection
    {
        return Message::where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }
        if (! empty($filters['recipient_type'])) {
            $query->where('recipient_type', $filters['recipient_type']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $filters['scheduled_from']);
        }
        if (! empty($filters['scheduled_to'])) {
            $query->where('scheduled_at', '<=', $filters['scheduled_to']);
        }
    }

    protected function applyUserMessageFilters(Builder $query, array $filters): void
    {
        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }
        if (! empty($filters['type'])) {
            $query->whereHas('message', static function ($q) use ($filters) { $q->where('type', $filters['type']); });
        }
        if (! empty($filters['priority'])) {
            $query->whereHas('message', static function ($q) use ($filters) { $q->where('priority', $filters['priority']); });
        }
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['keyword'])) {
            $query->whereHas('message', static function ($q) use ($filters) {
                $q->where(static function ($subQ) use ($filters) {
                    $subQ->where('title', 'like', "%{$filters['keyword']}%")
                        ->orWhere('content', 'like', "%{$filters['keyword']}%");
                });
            });
        }
    }
}
