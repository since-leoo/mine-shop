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
use Hyperf\Context\ApplicationContext;
use App\Domain\Infrastructure\SystemMessage\Event\NotificationFailed;
use App\Domain\Infrastructure\SystemMessage\Event\NotificationSent;
use App\Infrastructure\Model\SystemMessage\Message;
use App\Infrastructure\Model\SystemMessage\MessageDeliveryLog;
use App\Infrastructure\Model\SystemMessage\UserNotificationPreference;
use App\Domain\Infrastructure\SystemMessage\Repository\UserPreferenceRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class NotificationService
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        protected UserPreferenceRepository $preferenceRepository
    ) {}

    public function send(Message $message, int $userId, string $channel): bool
    {
        try {
            if (! $this->shouldSendNotification($message, $userId, $channel)) {
                logger()->info('Notification skipped due to user preferences', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
                return false;
            }
            if ($this->isInDoNotDisturbTime($userId)) {
                logger()->info('Notification skipped due to do not disturb time', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
                return false;
            }
            $result = $this->sendByChannel($message, $userId, $channel);
            $this->logDelivery($message, $userId, $channel, $result);
            if ($result) {
                $this->getEventDispatcher()->dispatch(new NotificationSent($message, $userId, $channel));
                logger()->info('Notification sent successfully', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
            }
            return $result;
        } catch (\Throwable $e) {
            $this->logDelivery($message, $userId, $channel, false, $e->getMessage());
            $this->getEventDispatcher()->dispatch(new NotificationFailed($message, $userId, $channel, $e->getMessage()));
            logger()->error('Failed to send notification', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function batchSend(Message $message, array $userIds, string $channel): array
    {
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0, 'details' => []];
        foreach ($userIds as $userId) {
            try {
                $sent = $this->send($message, $userId, $channel);
                if ($sent) { ++$results['success']; } else { ++$results['skipped']; }
                $results['details'][$userId] = $sent ? 'sent' : 'skipped';
            } catch (\Throwable $e) {
                ++$results['failed'];
                $results['details'][$userId] = 'failed: ' . $e->getMessage();
            }
        }
        return $results;
    }

    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return $this->preferenceRepository->getUserPreference($userId);
    }

    public function updateUserPreference(int $userId, array $data): UserNotificationPreference
    {
        return $this->preferenceRepository->createOrUpdate($userId, $data);
    }

    public function resetUserPreference(int $userId): bool
    {
        return $this->preferenceRepository->resetToDefault($userId);
    }

    public function getDefaultPreferences(): array
    {
        return [
            'channel_preferences' => config('system_message.notification.default_channels', ['database' => true, 'email' => false, 'sms' => false, 'push' => false]),
            'type_preferences' => config('system_message.notification.default_types', ['system' => true, 'announcement' => true, 'alert' => true, 'reminder' => true, 'marketing' => false]),
            'do_not_disturb_enabled' => false,
            'do_not_disturb_start' => '22:00:00',
            'do_not_disturb_end' => '08:00:00',
            'min_priority' => 1,
        ];
    }

    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        return $this->preferenceRepository->updateChannelPreferences($userId, $channels);
    }

    public function updateTypePreferences(int $userId, array $types): bool
    {
        return $this->preferenceRepository->updateTypePreferences($userId, $types);
    }

    public function setDoNotDisturbTime(int $userId, string $startTime, string $endTime, bool $enabled = true): bool
    {
        return $this->preferenceRepository->setDoNotDisturbTime($userId, $startTime, $endTime, $enabled);
    }

    public function toggleDoNotDisturb(int $userId, bool $enabled): bool
    {
        return $this->preferenceRepository->toggleDoNotDisturb($userId, $enabled);
    }

    public function setMinPriority(int $userId, int $priority): bool
    {
        return $this->preferenceRepository->setMinPriority($userId, $priority);
    }

    public function isDoNotDisturbActive(int $userId): bool
    {
        return $this->isInDoNotDisturbTime($userId);
    }

    protected function sendByChannel(Message $message, int $userId, string $channel): bool
    {
        return match ($channel) {
            'database' => $this->sendDatabaseNotification($message, $userId),
            'socketio', 'websocket' => $this->sendRealtimeNotification($message, $userId, $channel),
            'email' => $this->sendEmailNotification($message, $userId),
            'sms' => $this->sendSmsNotification($message, $userId),
            'push' => $this->sendPushNotification($message, $userId),
            'miniapp' => $this->sendMiniappNotification($message, $userId),
            default => throw new \InvalidArgumentException("Unsupported notification channel: {$channel}"),
        };
    }

    protected function sendDatabaseNotification(Message $message, int $userId): bool { return true; }

    protected function sendRealtimeNotification(Message $message, int $userId, string $channel): bool
    {
        logger()->info('Realtime notification skipped (not implemented)', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
        return true;
    }

    protected function sendEmailNotification(Message $message, int $userId): bool
    {
        $user = $this->getUserById($userId);
        if (! $user || empty($user->email)) { return false; }
        logger()->info('Email notification skipped (mail service not configured)', ['message_id' => $message->id, 'user_id' => $userId]);
        return false;
    }

    protected function sendSmsNotification(Message $message, int $userId): bool
    {
        $user = $this->getUserById($userId);
        if (! $user || empty($user->phone)) { return false; }
        logger()->info('SMS notification skipped (sms service not configured)', ['message_id' => $message->id, 'user_id' => $userId]);
        return false;
    }

    protected function sendPushNotification(Message $message, int $userId): bool
    {
        logger()->info('Push notification skipped (push service not configured)', ['message_id' => $message->id, 'user_id' => $userId]);
        return false;
    }

    protected function sendMiniappNotification(Message $message, int $userId): bool
    {
        logger()->info('Miniapp notification skipped (miniapp service not configured)', ['message_id' => $message->id, 'user_id' => $userId]);
        return false;
    }

    protected function shouldSendNotification(Message $message, int $userId, string $channel): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        if (! $preference) { return $this->getDefaultChannelSetting($channel); }
        if (! $preference->getChannelSetting($channel)) { return false; }
        if (! $preference->getTypeSetting($message->type)) { return false; }
        $minPriority = $preference->min_priority ?? 1;
        if ($message->priority < $minPriority) { return false; }
        return true;
    }

    protected function isInDoNotDisturbTime(int $userId): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        if (! $preference || ! $preference->do_not_disturb_enabled) { return false; }
        $now = Carbon::now();
        $startTime = Carbon::createFromTimeString($preference->do_not_disturb_start ?? '22:00:00');
        $endTime = Carbon::createFromTimeString($preference->do_not_disturb_end ?? '08:00:00');
        if ($startTime->greaterThan($endTime)) {
            return $now->greaterThanOrEqualTo($startTime) || $now->lessThanOrEqualTo($endTime);
        }
        return $now->between($startTime, $endTime);
    }

    protected function logDelivery(Message $message, int $userId, string $channel, bool $success, ?string $error = null): void
    {
        MessageDeliveryLog::create([
            'message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel,
            'status' => $success ? MessageDeliveryLog::STATUS_DELIVERED : MessageDeliveryLog::STATUS_FAILED,
            'error_message' => $error, 'sent_at' => Carbon::now(),
        ]);
    }

    protected function formatEmailContent(Message $message): string
    {
        $template = config('system_message.email.template', 'default');
        return view($template, ['message' => $message, 'title' => $message->title, 'content' => $message->content, 'type' => $message->type, 'priority' => $message->priority])->render();
    }

    protected function formatSmsContent(Message $message): string
    {
        $maxLength = config('system_message.sms.max_length', 70);
        $content = $message->title;
        if (mb_strlen($content) > $maxLength) { $content = mb_substr($content, 0, $maxLength - 3) . '...'; }
        return $content;
    }

    protected function formatPushContent(Message $message): string
    {
        $maxLength = config('system_message.push.max_length', 100);
        $content = strip_tags($message->content);
        if (mb_strlen($content) > $maxLength) { $content = mb_substr($content, 0, $maxLength - 3) . '...'; }
        return $content;
    }

    protected function getDefaultChannelSetting(string $channel): bool
    {
        $defaults = config('system_message.notification.default_channels', ['database' => true, 'email' => false, 'sms' => false, 'push' => false]);
        return $defaults[$channel] ?? false;
    }

    protected function getUserById(int $userId)
    {
        return \App\Infrastructure\Model\Permission\User::find($userId);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
