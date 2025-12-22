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

namespace Plugin\Since\SystemMessage\Repository;

use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\UserMessage;

class MessageRepository
{
    /**
     * 创建消息
     */
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    /**
     * 根据ID查找消息
     */
    public function findById(int $id): ?Message
    {
        return Message::find($id);
    }

    /**
     * 获取消息列表
     */
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
            'data' => $messages,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ];
    }

    /**
     * 获取用户消息
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = UserMessage::with(['message'])
            ->where('user_id', $userId)
            ->where('is_deleted', false);

        $this->applyUserMessageFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取用户消息（分页）
     */
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
            'data' => $messages,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ];
    }

    /**
     * 搜索消息
     */
    public function search(string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = Message::query();
        
        // 搜索关键词
        $query->where(function ($q) use ($keyword) {
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
            'data' => $messages,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
            'keyword' => $keyword,
        ];
    }

    /**
     * 获取统计信息
     */
    public function getStatistics(): array
    {
        $total = Message::count();
        $sent = Message::where('status', Message::STATUS_SENT)->count();
        $draft = Message::where('status', Message::STATUS_DRAFT)->count();
        $scheduled = Message::where('status', Message::STATUS_SCHEDULED)->count();
        $failed = Message::where('status', Message::STATUS_FAILED)->count();

        // 按类型统计
        $byType = Message::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // 最近7天的消息数量
        $recentMessages = Message::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // 用户消息统计
        $totalUserMessages = UserMessage::count();
        $readMessages = UserMessage::where('is_read', true)->count();
        $unreadMessages = UserMessage::where('is_read', false)->where('is_deleted', false)->count();

        return [
            'messages' => [
                'total' => $total,
                'sent' => $sent,
                'draft' => $draft,
                'scheduled' => $scheduled,
                'failed' => $failed,
                'by_type' => $byType,
                'recent' => $recentMessages,
            ],
            'user_messages' => [
                'total' => $totalUserMessages,
                'read' => $readMessages,
                'unread' => $unreadMessages,
                'read_rate' => $totalUserMessages > 0 ? round(($readMessages / $totalUserMessages) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * 获取热门消息
     */
    public function getPopularMessages(int $limit = 10): Collection
    {
        return Message::withCount('userMessages')
            ->where('status', Message::STATUS_SENT)
            ->orderBy('user_messages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取最近消息
     */
    public function getRecentMessages(int $days = 7, int $limit = 20): Collection
    {
        return Message::where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取待发送的消息
     */
    public function getPendingMessages(): Collection
    {
        return Message::pendingSend()->get();
    }

    /**
     * 应用过滤条件
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (!empty($filters['recipient_type'])) {
            $query->where('recipient_type', $filters['recipient_type']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['scheduled_from'])) {
            $query->where('scheduled_at', '>=', $filters['scheduled_from']);
        }

        if (!empty($filters['scheduled_to'])) {
            $query->where('scheduled_at', '<=', $filters['scheduled_to']);
        }
    }

    /**
     * 应用用户消息过滤条件
     */
    protected function applyUserMessageFilters(Builder $query, array $filters): void
    {
        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        if (!empty($filters['type'])) {
            $query->whereHas('message', function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        if (!empty($filters['priority'])) {
            $query->whereHas('message', function ($q) use ($filters) {
                $q->where('priority', $filters['priority']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['keyword'])) {
            $query->whereHas('message', function ($q) use ($filters) {
                $q->where(function ($subQ) use ($filters) {
                    $subQ->where('title', 'like', "%{$filters['keyword']}%")
                         ->orWhere('content', 'like', "%{$filters['keyword']}%");
                });
            });
        }
    }
}