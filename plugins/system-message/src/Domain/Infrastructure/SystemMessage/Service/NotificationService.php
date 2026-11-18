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

namespace Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service;

use App\Infrastructure\Error\SystemMessageError;
use App\Infrastructure\Model\Permission\User;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Plugin\Sms\Model\SmsMessage;
use Plugin\Sms\Service\SmsSenderInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\NotificationFailed;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\NotificationSent;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\UserPreferenceRepository;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\MessageDeliveryLog;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserMessage;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserNotificationPreference;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class NotificationService
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        protected UserPreferenceRepository $preferenceRepository,
        protected SmsSenderInterface $smsSender,
        protected MiniAppInterface $miniApp,
        protected NotificationTransportInterface $transport
    ) {}

    /**
     * Send a notification to a user via a specific channel.
     */
    public function send(Message $message, int $userId, string $channel): bool
    {
        if ($this->hasSuccessfulDelivery($message->id, $userId, $channel)) {
            return true;
        }
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
            if (! $result) {
                throw SystemMessageError::CHANNEL_UNCONFIRMED->internal([$channel]);
            }
            $this->logDelivery($message, $userId, $channel, true);
            $this->getEventDispatcher()->dispatch(new NotificationSent($message, $userId, $channel));
            logger()->info('Notification sent successfully', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
            return true;
        } catch (\Throwable $e) {
            $this->logDelivery($message, $userId, $channel, false, $e->getMessage());
            $this->getEventDispatcher()->dispatch(new NotificationFailed($message, $userId, $channel, $e->getMessage()));
            logger()->error('Failed to send notification', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function hasSuccessfulDelivery(int $messageId, int $userId, string $channel): bool
    {
        return MessageDeliveryLog::query()->where('message_id', $messageId)
            ->where('user_id', $userId)->where('channel', $channel)
            ->whereIn('status', [MessageDeliveryLog::STATUS_SENT, MessageDeliveryLog::STATUS_DELIVERED])->exists();
    }

    /**
     * Get user notification preferences.
     */
    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return $this->preferenceRepository->getUserPreference($userId);
    }

    /**
     * Update user notification preferences.
     */
    public function updateUserPreference(int $userId, array $data): UserNotificationPreference
    {
        return $this->preferenceRepository->createOrUpdate($userId, $data);
    }

    /**
     * Reset user notification preferences to default.
     */
    public function resetUserPreference(int $userId): bool
    {
        return $this->preferenceRepository->resetToDefault($userId);
    }

    /**
     * Get default notification preferences.
     */
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

    /**
     * Update channel preferences for a user.
     */
    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        return $this->preferenceRepository->updateChannelPreferences($userId, $channels);
    }

    /**
     * Update type preferences for a user.
     */
    public function updateTypePreferences(int $userId, array $types): bool
    {
        return $this->preferenceRepository->updateTypePreferences($userId, $types);
    }

    /**
     * Set the do not disturb time for a user.
     */
    public function setDoNotDisturbTime(int $userId, string $startTime, string $endTime, bool $enabled = true): bool
    {
        return $this->preferenceRepository->setDoNotDisturbTime($userId, $startTime, $endTime, $enabled);
    }

    /**
     * Toggle the do not disturb status for a user.
     */
    public function toggleDoNotDisturb(int $userId, bool $enabled): bool
    {
        return $this->preferenceRepository->toggleDoNotDisturb($userId, $enabled);
    }

    /**
     * Set the minimum priority for notifications.
     */
    public function setMinPriority(int $userId, int $priority): bool
    {
        return $this->preferenceRepository->setMinPriority($userId, $priority);
    }

    /**
     * Check if do not disturb is active for a user.
     */
    public function isDoNotDisturbActive(int $userId): bool
    {
        return $this->isInDoNotDisturbTime($userId);
    }

    /**
     * Send a notification by channel.
     */
    protected function sendByChannel(Message $message, int $userId, string $channel): bool
    {
        return match ($channel) {
            'database' => $this->sendDatabaseNotification($message, $userId),
            'socketio', 'websocket' => $this->sendRealtimeNotification($message, $userId, $channel),
            'email' => $this->sendEmailNotification($message, $userId),
            'sms' => $this->sendSmsNotification($message, $userId),
            'push' => $this->sendPushNotification($message, $userId),
            'miniapp' => $this->sendMiniappNotification($message, $userId),
            default => throw SystemMessageError::CHANNEL_UNSUPPORTED->invalid([$channel]),
        };
    }

    /**
     * Send a database notification.
     */
    protected function sendDatabaseNotification(Message $message, int $userId): bool
    {
        return UserMessage::query()->where('message_id', $message->id)
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->exists();
    }

    /**
     * Send a realtime notification.
     */
    protected function sendRealtimeNotification(Message $message, int $userId, string $channel): bool
    {
        $this->transport->gateway($channel, $userId, ['message_id' => $message->id, 'title' => $message->title, 'content' => $message->content, 'type' => $message->type, 'priority' => $message->priority]);
        return true;
    }

    /**
     * Send an email notification.
     */
    protected function sendEmailNotification(Message $message, int $userId): bool
    {
        $user = $this->getUserById($userId);
        if (! $user || empty($user->email)) {
            return false;
        }
        $this->transport->email((string) $user->email, $message->title, $this->formatEmailContent($message));
        return true;
    }

    /**
     * Send an SMS notification.
     */
    protected function sendSmsNotification(Message $message, int $userId): bool
    {
        $user = $this->getUserById($userId);
        if (! $user || empty($user->phone)) {
            return false;
        }
        $variables = \is_array($message->template_variables) ? $message->template_variables : [];
        $this->smsSender->send(new SmsMessage(
            (string) $user->phone,
            $variables,
            isset($message->extra_data['template_code']) ? (string) $message->extra_data['template_code'] : null,
            $this->formatSmsContent($message),
        ));
        return true;
    }

    /**
     * Send a push notification.
     */
    protected function sendPushNotification(Message $message, int $userId): bool
    {
        $this->transport->gateway('push', $userId, ['message_id' => $message->id, 'title' => $message->title, 'content' => $this->formatPushContent($message), 'type' => $message->type, 'priority' => $message->priority]);
        return true;
    }

    /**
     * Send a miniapp notification.
     */
    protected function sendMiniappNotification(Message $message, int $userId): bool
    {
        if ($message->recipient_type !== 'user' || array_map('intval', $message->recipient_ids ?? []) !== [$userId]) {
            throw SystemMessageError::MINIAPP_SINGLE_RECIPIENT_REQUIRED->exception();
        }
        $extra = \is_array($message->extra_data) ? $message->extra_data : [];
        $openid = (string) ($extra['openid'] ?? $extra['miniapp_openid'] ?? '');
        $templateId = (string) ($extra['miniapp_template_id'] ?? $extra['template_id'] ?? '');
        if ($openid === '' || $templateId === '') {
            logger()->warning('Miniapp notification skipped (openid or template_id is missing)', ['message_id' => $message->id, 'user_id' => $userId]);
            return false;
        }
        $variables = \is_array($message->template_variables) ? $message->template_variables : [];
        $data = $extra['miniapp_data'] ?? $variables;
        if (! \is_array($data)) {
            $data = [];
        }
        $receipt = $this->miniApp->sendSubscribeMessage(
            $openid,
            $templateId,
            $data,
            (string) ($extra['miniapp_page'] ?? ''),
        );
        if (! in_array($receipt['errcode'] ?? null, [0, '0'], true)) {
            throw SystemMessageError::MINIAPP_RECEIPT_FAILED->internal([$receipt['errcode'] ?? 'missing']);
        }
        return true;
    }

    /**
     * Determine if the given notification should be sent.
     */
    protected function shouldSendNotification(Message $message, int $userId, string $channel): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        if (! $preference) {
            return $this->getDefaultChannelSetting($channel);
        }
        if (! $preference->getChannelSetting($channel)) {
            return false;
        }
        if (! $preference->getTypeSetting($message->type)) {
            return false;
        }
        $minPriority = $preference->min_priority ?? 1;
        if ($message->priority < $minPriority) {
            return false;
        }
        return true;
    }

    /**
     * Check if the current time is within the user's do not disturb period.
     */
    protected function isInDoNotDisturbTime(int $userId): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        if (! $preference || ! $preference->do_not_disturb_enabled) {
            return false;
        }
        $now = Carbon::now();
        $startTime = Carbon::createFromTimeString($preference->do_not_disturb_start ?? '22:00:00');
        $endTime = Carbon::createFromTimeString($preference->do_not_disturb_end ?? '08:00:00');
        if ($startTime->greaterThan($endTime)) {
            return $now->greaterThanOrEqualTo($startTime) || $now->lessThanOrEqualTo($endTime);
        }
        return $now->between($startTime, $endTime);
    }

    /**
     * Log the delivery of a notification.
     */
    protected function logDelivery(Message $message, int $userId, string $channel, bool $success, ?string $error = null): void
    {
        MessageDeliveryLog::query()->create([
            'message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel,
            'status' => $success ? ($channel === 'database' ? MessageDeliveryLog::STATUS_DELIVERED : MessageDeliveryLog::STATUS_SENT) : MessageDeliveryLog::STATUS_FAILED,
            'attempt_count' => 1, 'error_message' => $error, 'sent_at' => $success ? Carbon::now() : null,
            'delivered_at' => $success && $channel === 'database' ? Carbon::now() : null,
        ]);
    }

    /**
     * Format the content of an email notification.
     */
    protected function formatEmailContent(Message $message): string
    {
        $title = htmlspecialchars($message->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $content = nl2br(htmlspecialchars(strip_tags($message->content), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        return '<!doctype html><html><head><meta charset="UTF-8"></head><body><h1>' . $title . '</h1><p>' . $content . '</p></body></html>';
    }

    /**
     * Format the content of an SMS notification.
     */
    protected function formatSmsContent(Message $message): string
    {
        $maxLength = config('system_message.sms.max_length', 70);
        $content = $message->title;
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength - 3) . '...';
        }
        return $content;
    }

    /**
     * Format the content of a push notification.
     */
    protected function formatPushContent(Message $message): string
    {
        $maxLength = config('system_message.push.max_length', 100);
        $content = strip_tags($message->content);
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength - 3) . '...';
        }
        return $content;
    }

    /**
     * Get the default setting for a notification channel.
     */
    protected function getDefaultChannelSetting(string $channel): bool
    {
        $defaults = config('system_message.notification.default_channels', ['database' => true, 'email' => false, 'sms' => false, 'push' => false]);
        return $defaults[$channel] ?? false;
    }

    /**
     * Get a user by their ID.
     *
     * @return null|User
     */
    protected function getUserById(int $userId)
    {
        return User::find($userId);
    }

    /**
     * Get the event dispatcher.
     */
    private function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        }
        return $this->eventDispatcher;
    }
}
