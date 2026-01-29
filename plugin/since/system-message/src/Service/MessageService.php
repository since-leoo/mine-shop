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

namespace Plugin\Since\SystemMessage\Service;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Plugin\Since\SystemMessage\Enum\MessageChannel;
use Plugin\Since\SystemMessage\Enum\MessageStatus;
use Plugin\Since\SystemMessage\Enum\MessageType;
use Plugin\Since\SystemMessage\Enum\RecipientType;
use Plugin\Since\SystemMessage\Event\MessageSendFailed;
use Plugin\Since\SystemMessage\Event\MessageSent;
use Plugin\Since\SystemMessage\Job\SendMessageJob;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\UserMessage;
use Plugin\Since\SystemMessage\Repository\MessageRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class MessageService
{
    protected MessageRepository $repository;

    protected NotificationService $notificationService;

    protected DriverFactory $queueDriverFactory;

    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        MessageRepository $repository,
        NotificationService $notificationService,
        DriverFactory $queueDriverFactory
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
        $this->queueDriverFactory = $queueDriverFactory;
    }

    /**
     * 创建消息.
     */
    public function create(array $data): Message
    {
        try {
            // 验证数据
            $this->validateMessageData($data);

            // 设置默认值
            $data = $this->setDefaultValues($data);

            // 创建消息
            $message = $this->repository->create($data);

            system_message_logger()->info('Message created', [
                'message_id' => $message->id,
                'title' => $message->title,
                'type' => $message->type,
                'recipient_type' => $message->recipient_type,
            ]);

            return $message;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to create message', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 发送消息.
     */
    public function send(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) {
                throw new \InvalidArgumentException("Message not found: {$messageId}");
            }

            if (! $message->canSend()) {
                throw new \InvalidArgumentException("Message cannot be sent: {$messageId}");
            }

            // 标记为发送中
            $message->markAsSending();

            // 获取收件人
            $recipients = $message->getRecipients();
            if ($recipients->isEmpty()) {
                throw new \InvalidArgumentException("No recipients found for message: {$messageId}");
            }

            // 创建用户消息关联
            $this->createUserMessages($message, $recipients);

            // 异步发送通知
            $this->queueNotifications($message, $recipients);

            // 标记为已发送
            $message->markAsSent();

            // 触发发送后事件
            $this->getEventDispatcher()->dispatch(new MessageSent($message));

            system_message_logger()->info('Message sent', [
                'message_id' => $message->id,
                'recipient_count' => $recipients->count(),
            ]);

            return true;
        } catch (\Throwable $e) {
            // 标记为发送失败
            if (isset($message)) {
                $message->markAsFailed();
                $this->getEventDispatcher()->dispatch(new MessageSendFailed($message, $e->getMessage()));
            }

            system_message_logger()->error('Failed to send message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 调度消息.
     */
    public function schedule(int $messageId, Carbon $scheduledAt): bool
    {
        $message = $this->repository->findById($messageId);
        if (! $message) {
            throw new \InvalidArgumentException("Message not found: {$messageId}");
        }

        return $message->update([
            'scheduled_at' => $scheduledAt,
            'status' => Message::STATUS_SCHEDULED,
        ]);
    }

    /**
     * 获取用户消息（分页）.
     */
    public function getUserMessages(int $userId, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->getUserMessages($userId, $filters, $page, $pageSize);
    }

    /**
     * 获取单个用户消息.
     */
    public function getUserMessage(int $userId, int $messageId): ?UserMessage
    {
        return UserMessage::where('user_id', $userId)
            ->where('message_id', $messageId)
            ->with('message')
            ->first();
    }

    /**
     * 标记消息为已读.
     */
    public function markAsRead(int $userId, int $messageId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if (! $userMessage) {
            return false;
        }

        return $userMessage->markAsRead();
    }

    /**
     * 批量标记消息为已读.
     */
    public function batchMarkAsRead(int $userId, array $messageIds): int
    {
        return UserMessage::batchMarkAsRead($userId, $messageIds);
    }

    /**
     * 标记所有消息为已读.
     */
    public function markAllAsRead(int $userId): int
    {
        return UserMessage::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }

    /**
     * 删除用户消息.
     */
    public function deleteUserMessage(int $messageId, int $userId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if (! $userMessage) {
            return false;
        }

        return $userMessage->softDelete();
    }

    /**
     * 批量删除用户消息.
     */
    public function batchDeleteUserMessages(array $messageIds, int $userId): int
    {
        return UserMessage::batchSoftDelete($userId, $messageIds);
    }

    /**
     * 获取用户消息类型统计
     */
    public function getUserMessageTypeStats(int $userId): array
    {
        $stats = UserMessage::where('user_id', $userId)
            ->where('is_deleted', false)
            ->join('messages', 'user_messages.message_id', '=', 'messages.id')
            ->selectRaw('messages.type, COUNT(*) as total, SUM(CASE WHEN user_messages.is_read = 0 THEN 1 ELSE 0 END) as unread')
            ->groupBy('messages.type')
            ->get();

        return $stats->toArray();
    }

    /**
     * 搜索用户消息.
     */
    public function searchUserMessages(int $userId, string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = UserMessage::where('user_id', $userId)
            ->where('is_deleted', false)
            ->whereHas('message', static function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            })
            ->with('message');

        // 应用过滤条件
        if (isset($filters['is_read']) && $filters['is_read'] !== null) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        if (! empty($filters['type'])) {
            $query->whereHas('message', static function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        if (! empty($filters['priority'])) {
            $query->whereHas('message', static function ($q) use ($filters) {
                $q->where('priority', '>=', $filters['priority']);
            });
        }

        $total = $query->count();
        $data = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取用户未读消息数量.
     */
    public function getUnreadCount(int $userId): int
    {
        return UserMessage::getUnreadCount($userId);
    }

    /**
     * 更新消息.
     */
    public function update(int $messageId, array $data): Message
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) {
                throw new \InvalidArgumentException("Message not found: {$messageId}");
            }

            // 记录变更
            $changes = [];
            foreach ($data as $key => $value) {
                if ($message->{$key} !== $value) {
                    $changes[$key] = [
                        'old' => $message->{$key},
                        'new' => $value,
                    ];
                }
            }

            // 更新消息
            $message->update($data);

            system_message_logger()->info('Message updated', [
                'message_id' => $message->id,
                'changes' => array_keys($changes),
            ]);

            return $message;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to update message', [
                'message_id' => $messageId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 删除消息.
     */
    public function delete(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) {
                return false;
            }

            // 软删除消息
            $result = $message->delete();

            system_message_logger()->info('Message deleted', [
                'message_id' => $message->id,
            ]);

            return $result;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to delete message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取消息统计
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * 处理调度消息.
     */
    public function processScheduledMessages(): int
    {
        $messages = Message::pendingSend()->get();
        $processed = 0;

        foreach ($messages as $message) {
            try {
                $this->send($message->id);
                ++$processed;
            } catch (\Throwable $e) {
                system_message_logger()->error('Failed to process scheduled message', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * 获取仓库实例.
     */
    public function getRepository(): MessageRepository
    {
        return $this->repository;
    }

    /**
     * 清理过期消息.
     */
    public function cleanupExpiredMessages(): int
    {
        $retentionDays = config('system_message.message.retention_days', 90);
        $expiredDate = Carbon::now()->subDays($retentionDays);

        return Message::where('created_at', '<', $expiredDate)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * 验证消息数据.
     */
    protected function validateMessageData(array $data): void
    {
        $required = ['title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // 验证标题长度
        $maxTitleLength = config('system_message.message.max_title_length', 255);
        if (mb_strlen($data['title']) > $maxTitleLength) {
            throw new \InvalidArgumentException("Title too long, max {$maxTitleLength} characters");
        }

        // 验证内容长度
        $maxContentLength = config('system_message.message.max_content_length', 10000);
        if (mb_strlen($data['content']) > $maxContentLength) {
            throw new \InvalidArgumentException("Content too long, max {$maxContentLength} characters");
        }

        // 验证消息类型
        if (isset($data['type']) && ! \in_array($data['type'], MessageType::values(), true)) {
            throw new \InvalidArgumentException("Invalid message type: {$data['type']}");
        }

        // 验证收件人类型
        if (isset($data['recipient_type']) && ! \in_array($data['recipient_type'], RecipientType::values(), true)) {
            throw new \InvalidArgumentException("Invalid recipient type: {$data['recipient_type']}");
        }

        // 验证优先级
        if (isset($data['priority']) && ($data['priority'] < 1 || $data['priority'] > 5)) {
            throw new \InvalidArgumentException('Priority must be between 1 and 5');
        }
    }

    /**
     * 设置默认值
     */
    protected function setDefaultValues(array $data): array
    {
        $defaults = [
            'type' => MessageType::SYSTEM->value,
            'priority' => config('system_message.message.default_priority', 1),
            'recipient_type' => RecipientType::ALL->value,
            'status' => MessageStatus::DRAFT->value,
            'channels' => MessageChannel::defaults(),
        ];

        return array_merge($defaults, $data);
    }

    /**
     * 创建用户消息关联.
     */
    protected function createUserMessages(Message $message, Collection $recipients): void
    {
        $userMessages = [];
        $now = Carbon::now();

        foreach ($recipients as $user) {
            $userMessages[] = [
                'user_id' => $user->id,
                'message_id' => $message->id,
                'is_read' => false,
                'is_deleted' => false,
                'created_at' => $now,
            ];
        }

        // 批量插入
        if (! empty($userMessages)) {
            Db::table('user_messages')->insert($userMessages);
        }
    }

    /**
     * 队列通知任务
     */
    protected function queueNotifications(Message $message, Collection $recipients): void
    {
        $channels = $message->channels ?? ['database'];

        // 使用配置的队列通道，如果不存在则使用默认通道
        $queueChannel = config('system_message.queue.channel', 'default');
        try {
            $driver = $this->queueDriverFactory->get($queueChannel);
        } catch (\Throwable $e) {
            // 如果配置的队列不存在，回退到默认队列
            $driver = $this->queueDriverFactory->get('default');
        }

        foreach ($recipients as $user) {
            foreach ($channels as $channel) {
                $job = new SendMessageJob($message->id, $user->id, $channel);
                $driver->push($job);
            }
        }
    }

    /**
     * 懒加载获取 EventDispatcher
     * 避免在 Listener 注册阶段产生循环依赖.
     */
    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
