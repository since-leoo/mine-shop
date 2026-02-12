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

namespace App\Domain\Infrastructure\SystemMessage\Service;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageStatus;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Enum\RecipientType;
use App\Domain\Infrastructure\SystemMessage\Event\MessageSendFailed;
use App\Domain\Infrastructure\SystemMessage\Event\MessageSent;
use App\Domain\Infrastructure\SystemMessage\Job\SendMessageJob;
use App\Infrastructure\Model\SystemMessage\Message;
use App\Infrastructure\Model\SystemMessage\UserMessage;
use App\Domain\Infrastructure\SystemMessage\Repository\MessageRepository;
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

    public function create(array $data): Message
    {
        try {
            $this->validateMessageData($data);
            $data = $this->setDefaultValues($data);
            $message = $this->repository->create($data);
            system_message_logger()->info('Message created', [
                'message_id' => $message->id, 'title' => $message->title,
                'type' => $message->type, 'recipient_type' => $message->recipient_type,
            ]);
            return $message;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to create message', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function send(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) { throw new \InvalidArgumentException("Message not found: {$messageId}"); }
            if (! $message->canSend()) { throw new \InvalidArgumentException("Message cannot be sent: {$messageId}"); }
            $recipients = $message->getRecipients();
            if ($recipients->isEmpty()) { throw new \InvalidArgumentException("No recipients found for message: {$messageId}"); }
            Db::transaction(function () use ($message, $recipients) {
                $message->markAsSending();
                $this->createUserMessages($message, $recipients);
                $message->markAsSent();
            });
            $this->queueNotifications($message, $recipients);
            $this->getEventDispatcher()->dispatch(new MessageSent($message));
            system_message_logger()->info('Message sent', ['message_id' => $message->id, 'recipient_count' => $recipients->count()]);
            return true;
        } catch (\Throwable $e) {
            if (isset($message) && $message->isSending()) { $message->markAsFailed(); }
            if (isset($message)) { $this->getEventDispatcher()->dispatch(new MessageSendFailed($message, $e->getMessage())); }
            system_message_logger()->error('Failed to send message', ['message_id' => $messageId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function schedule(int $messageId, Carbon $scheduledAt): bool
    {
        $message = $this->repository->findById($messageId);
        if (! $message) { throw new \InvalidArgumentException("Message not found: {$messageId}"); }
        return $message->update(['scheduled_at' => $scheduledAt, 'status' => MessageStatus::SCHEDULED->value]);
    }

    public function getUserMessages(int $userId, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return $this->repository->getUserMessages($userId, $filters, $page, $pageSize);
    }

    public function getUserMessage(int $userId, int $messageId): ?UserMessage
    {
        return UserMessage::where('user_id', $userId)->where('message_id', $messageId)->with('message')->first();
    }

    public function markAsRead(int $userId, int $messageId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)->where('user_id', $userId)->first();
        if (! $userMessage) { return false; }
        return $userMessage->markAsRead();
    }

    public function batchMarkAsRead(int $userId, array $messageIds): int
    {
        return UserMessage::batchMarkAsRead($userId, $messageIds);
    }

    public function markAllAsRead(int $userId): int
    {
        return UserMessage::where('user_id', $userId)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);
    }

    public function deleteUserMessage(int $messageId, int $userId): bool
    {
        $userMessage = UserMessage::where('message_id', $messageId)->where('user_id', $userId)->first();
        if (! $userMessage) { return false; }
        return $userMessage->softDelete();
    }

    public function batchDeleteUserMessages(array $messageIds, int $userId): int
    {
        return UserMessage::batchSoftDelete($userId, $messageIds);
    }

    public function getUserMessageTypeStats(int $userId): array
    {
        $stats = UserMessage::where('user_id', $userId)->where('is_deleted', false)
            ->join('messages', 'user_messages.message_id', '=', 'messages.id')
            ->selectRaw('messages.type, COUNT(*) as total, SUM(CASE WHEN user_messages.is_read = 0 THEN 1 ELSE 0 END) as unread')
            ->groupBy('messages.type')->get();
        return $stats->toArray();
    }

    public function searchUserMessages(int $userId, string $keyword, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $filters['keyword'] = $keyword;
        return $this->repository->getUserMessages($userId, $filters, $page, $pageSize);
    }

    public function getUnreadCount(int $userId): int
    {
        return UserMessage::getUnreadCount($userId);
    }

    public function update(int $messageId, array $data): Message
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) { throw new \InvalidArgumentException("Message not found: {$messageId}"); }
            $changes = [];
            foreach ($data as $key => $value) {
                if ($message->{$key} !== $value) { $changes[$key] = ['old' => $message->{$key}, 'new' => $value]; }
            }
            $message->update($data);
            system_message_logger()->info('Message updated', ['message_id' => $message->id, 'changes' => array_keys($changes)]);
            return $message;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to update message', ['message_id' => $messageId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $messageId): bool
    {
        try {
            $message = $this->repository->findById($messageId);
            if (! $message) { return false; }
            $result = $message->delete();
            system_message_logger()->info('Message deleted', ['message_id' => $message->id]);
            return $result;
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to delete message', ['message_id' => $messageId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function processScheduledMessages(): int
    {
        $messages = Message::pendingSend()->get();
        $processed = 0;
        foreach ($messages as $message) {
            try { $this->send($message->id); ++$processed; } catch (\Throwable $e) {
                system_message_logger()->error('Failed to process scheduled message', ['message_id' => $message->id, 'error' => $e->getMessage()]);
            }
        }
        return $processed;
    }

    public function getRepository(): MessageRepository
    {
        return $this->repository;
    }

    public function cleanupExpiredMessages(): int
    {
        $retentionDays = config('system_message.message.retention_days', 90);
        $expiredDate = Carbon::now()->subDays($retentionDays);
        $expiredMessageIds = Message::where('created_at', '<', $expiredDate)->pluck('id')->toArray();
        if (empty($expiredMessageIds)) { return 0; }
        UserMessage::whereIn('message_id', $expiredMessageIds)->delete();
        return Message::where('created_at', '<', $expiredDate)->forceDelete();
    }

    protected function validateMessageData(array $data): void
    {
        $required = ['title', 'content'];
        foreach ($required as $field) {
            if (empty($data[$field])) { throw new \InvalidArgumentException("Field {$field} is required"); }
        }
        $maxTitleLength = config('system_message.message.max_title_length', 255);
        if (mb_strlen($data['title']) > $maxTitleLength) { throw new \InvalidArgumentException("Title too long, max {$maxTitleLength} characters"); }
        $maxContentLength = config('system_message.message.max_content_length', 10000);
        if (mb_strlen($data['content']) > $maxContentLength) { throw new \InvalidArgumentException("Content too long, max {$maxContentLength} characters"); }
        if (isset($data['type']) && ! \in_array($data['type'], MessageType::values(), true)) { throw new \InvalidArgumentException("Invalid message type: {$data['type']}"); }
        if (isset($data['recipient_type']) && ! \in_array($data['recipient_type'], RecipientType::values(), true)) { throw new \InvalidArgumentException("Invalid recipient type: {$data['recipient_type']}"); }
        if (isset($data['priority']) && ($data['priority'] < 1 || $data['priority'] > 5)) { throw new \InvalidArgumentException('Priority must be between 1 and 5'); }
    }

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

    protected function createUserMessages(Message $message, Collection $recipients): void
    {
        $userMessages = [];
        $now = Carbon::now();
        foreach ($recipients as $user) {
            $userMessages[] = ['user_id' => $user->id, 'message_id' => $message->id, 'is_read' => false, 'is_deleted' => false, 'created_at' => $now];
        }
        if (! empty($userMessages)) { Db::table('user_messages')->insert($userMessages); }
    }

    protected function queueNotifications(Message $message, Collection $recipients): void
    {
        $channels = $message->channels ?? ['database'];
        $queueChannel = config('system_message.queue.channel', 'default');
        try { $driver = $this->queueDriverFactory->get($queueChannel); } catch (\Throwable $e) { $driver = $this->queueDriverFactory->get('default'); }
        foreach ($recipients as $user) {
            foreach ($channels as $channel) {
                $job = new SendMessageJob($message->id, $user->id, $channel);
                $driver->push($job);
            }
        }
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
