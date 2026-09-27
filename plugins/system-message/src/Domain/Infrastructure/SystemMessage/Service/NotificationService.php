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

use App\Infrastructure\Model\Permission\User;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Annotation\AsyncQueueMessage;
use Hyperf\Context\ApplicationContext;
use Plugin\Sms\Model\SmsMessage;
use Plugin\Sms\Service\SmsSenderInterface;
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
        protected MiniAppInterface $miniApp
    ) {}

    /**
     * Send a notification to a user via a specific channel.
     */
    #[AsyncQueueMessage]
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
            default => throw new \InvalidArgumentException("Unsupported notification channel: {$channel}"),
        };
    }

    /**
     * Send a database notification.
     */
    protected function sendDatabaseNotification(Message $message, int $userId): bool
    {
        return UserMessage::where('message_id', $message->id)
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->exists();
    }

    /**
     * Send a realtime notification.
     */
    protected function sendRealtimeNotification(Message $message, int $userId, string $channel): bool
    {
        logger()->info('Realtime notification skipped (not implemented)', ['message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel]);
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
        $from = (string) config('system_message.email.from', config('mail.from.address', ''));
        $headers = ['MIME-Version: 1.0', 'Content-type: text/html; charset=UTF-8'];
        if ($from !== '') {
            $headers[] = 'From: ' . $from;
        }
        $sent = mail((string) $user->email, $message->title, $this->formatEmailContent($message), implode("\r\n", $headers));
        if (! $sent) {
            logger()->warning('Email notification failed', ['message_id' => $message->id, 'user_id' => $userId, 'email' => $user->email]);
        }
        return $sent;
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
            (string) $message->extra_data['template_code'] ?? null,
            $this->formatSmsContent($message),
        ));
        return true;
    }

    /**
     * Send a push notification.
     */
    protected function sendPushNotification(Message $message, int $userId): bool
    {
        logger()->info('Push notification skipped (push service not configured)', ['message_id' => $message->id, 'user_id' => $userId]);
        return false;
    }

    /**
     * Send a miniapp notification.
     */
    protected function sendMiniappNotification(Message $message, int $userId): bool
    {
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
        $this->miniApp->sendSubscribeMessage(
            $openid,
            $templateId,
            $data,
            (string) ($extra['miniapp_page'] ?? ''),
        );
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
        MessageDeliveryLog::create([
            'message_id' => $message->id, 'user_id' => $userId, 'channel' => $channel,
            'status' => $success ? MessageDeliveryLog::STATUS_DELIVERED : MessageDeliveryLog::STATUS_FAILED,
            'error_message' => $error, 'sent_at' => Carbon::now(),
        ]);
    }

    /**
     * Format the content of an email notification.
     */
    protected function formatEmailContent(Message $message): string
    {
        $template = config('system_message.email.template', 'default');
        return view($template, ['message' => $message, 'title' => $message->title, 'content' => $message->content, 'type' => $message->type, 'priority' => $message->priority])->render();
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
