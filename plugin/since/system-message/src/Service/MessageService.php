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
use Plugin\Since\SystemMessage\Event\MessageCreated;
use Plugin\Since\SystemMessage\Event\MessageDeleted;
use Plugin\Since\SystemMessage\Event\MessageSendFailed;
use Plugin\Since\SystemMessage\Event\MessageSending;
use Plugin\Since\SystemMessage\Event\MessageSent;
use Plugin\Since\SystemMessage\Event\MessageUpdated;
use Plugin\Since\SystemMessage\Job\SendMessageJob;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\UserMessage;
use Plugin\Since\SystemMessage\Repository\MessageRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class MessageService
{
    protected MessageRepository $repository;
    protected NotificationService $notificationService;
    protected EventDispatcherInterface $eventDispatcher;
    protected DriverFactory $queueDriverFactory;

    public function __construct(
        MessageRepository $repository,
        NotificationService $notificationService,
        EventDispatcherInterface $eventDispatcher,
        DriverFactory $queueDriverFactory
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
        $this->eventDispatcher = $eventDispatcher;
        $this->queueDriverFactory = $queueDriverFactory;
    }

    /**
     * 创建消息
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
            
            // 触发事件
            $this->eventDispatcher->dispatch(new MessageCreated($message));
            
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
     * 发送消息
     */
    public function send(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (!$message) {
                throw new \InvalidArgumentException("Message not found: {$messageId}");
            }

            if (!$message->canSend()) {
                throw new \InvalidArgumentException("Message cannot be sent: {$messageId}");
            }

            // 触发发送前事件
            $this->eventDispatcher->dispatch(new MessageSending($message));

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
            $this->eventDispatcher->dispatch(new MessageSent($message));

            system_message_logger()->info('Message sent', [
                'message_id' => $message->id,
                'recipient_count' => $recipients->count(),
            ]);

            return true;
        } catch (\Throwable $e) {
            // 标记为发送失败
            if (isset($message)) {
                $message->markAsFailed();
                $this->eventDispatcher->dispatch(new MessageSendFailed($message, $e->getMessage()));
            }

            system_message_logger()->error('Failed to send message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 调度消息
     */
    public function schedule(int $messageId, Carbon $scheduledAt): bool
    {
        $message = $this->repository->findById($messageId);
        if (!$message) {
            throw new \InvalidArgumentException("Message not found: {$messageId}");
        }

        return $message->update([
            'scheduled_at' => $scheduledAt,
            'status' => Message::STATUS_SCHEDULED,
        ]);
    }

    /**
     * 获取用户消息
     */
    public function getByUser(int $userId, array $filters = []): Collection
    {
        return $this->repository->getByUser($userId, $filters);
    }

    /**
     * 获取用户消息（分页）
     */
    public function getUserMessages(int $userId, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->getUserMessages($userId, $filters, $page, $pageSize);
    }

    /**
     * 标记消息为已读
     */
    public function markAsRead(int $messageId, int $userId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userMessage) {
            return false;
        }

        return $userMessage->markAsRead();
    }

    /**
     * 批量标记消息为已读
     */
    public function batchMarkAsRead(array $messageIds, int $userId): int
    {
        return UserMessage::batchMarkAsRead($userId, $messageIds);
    }

    /**
     * 删除用户消息
     */
    public function deleteUserMessage(int $messageId, int $userId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userMessage) {
            return false;
        }

        return $userMessage->softDelete();
    }

    /**
     * 批量删除用户消息
     */
    public function batchDeleteUserMessages(array $messageIds, int $userId): int
    {
        return UserMessage::batchSoftDelete($userId, $messageIds);
    }

    /**
     * 获取用户未读消息数量
     */
    public function getUnreadCount(int $userId): int
    {
        return UserMessage::getUnreadCount($userId);
    }

    /**
     * 更新消息
     */
    public function update(int $messageId, array $data): Message
    {
        try {
            $message = $this->repository->findById($messageId);
            if (!$message) {
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

            // 触发事件
            if (!empty($changes)) {
                $this->eventDispatcher->dispatch(new MessageUpdated($message, $changes));
            }

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
     * 删除消息
     */
    public function delete(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (!$message) {
                return false;
            }

            // 软删除消息
            $result = $message->delete();

            // 触发事件
            $this->eventDispatcher->dispatch(new MessageDeleted($message));

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
     * 处理调度消息
     */
    public function processScheduledMessages(): int
    {
        $messages = Message::pendingSend()->get();
        $processed = 0;

        foreach ($messages as $message) {
            try {
                $this->send($message->id);
                $processed++;
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
     * 获取仓库实例
     */
    public function getRepository(): MessageRepository
    {
        return $this->repository;
    }

    /**
     * 清理过期消息
     */
    public function cleanupExpiredMessages(): int
    {
        $retentionDays = config('system_message.message.retention_days', 90);
        $expiredDate = now()->subDays($retentionDays);

        return Message::where('created_at', '<', $expiredDate)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * 验证消息数据
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
        if (isset($data['type']) && !in_array($data['type'], array_keys(Message::getTypes()))) {
            throw new \InvalidArgumentException("Invalid message type: {$data['type']}");
        }

        // 验证收件人类型
        if (isset($data['recipient_type']) && !in_array($data['recipient_type'], array_keys(Message::getRecipientTypes()))) {
            throw new \InvalidArgumentException("Invalid recipient type: {$data['recipient_type']}");
        }

        // 验证优先级
        if (isset($data['priority']) && ($data['priority'] < 1 || $data['priority'] > 5)) {
            throw new \InvalidArgumentException("Priority must be between 1 and 5");
        }
    }

    /**
     * 设置默认值
     */
    protected function setDefaultValues(array $data): array
    {
        $defaults = [
            'type' => Message::TYPE_SYSTEM,
            'priority' => config('system_message.message.default_priority', 1),
            'recipient_type' => Message::RECIPIENT_ALL,
            'status' => Message::STATUS_DRAFT,
            'channels' => ['socketio', 'websocket'],
        ];

        return array_merge($defaults, $data);
    }

    /**
     * 创建用户消息关联
     */
    protected function createUserMessages(Message $message, Collection $recipients): void
    {
        $userMessages = [];
        $now = now();

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
        if (!empty($userMessages)) {
            Db::table('user_messages')->insert($userMessages);
        }
    }

    /**
     * 队列通知任务
     */
    protected function queueNotifications(Message $message, Collection $recipients): void
    {
        $channels = $message->channels ?? ['websocket'];
        $driver = $this->queueDriverFactory->get('system_message');

        foreach ($recipients as $user) {
            foreach ($channels as $channel) {
                $job = new SendMessageJob($message->id, $user->id, $channel);
                $driver->push($job);
            }
        }
    }
}