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
use Hyperf\Context\ApplicationContext;
use Plugin\Since\SystemMessage\Event\NotificationFailed;
use Plugin\Since\SystemMessage\Event\NotificationSent;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\MessageDeliveryLog;
use Plugin\Since\SystemMessage\Model\UserNotificationPreference;
use Plugin\Since\SystemMessage\Repository\UserPreferenceRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class NotificationService
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        protected UserPreferenceRepository $preferenceRepository
    ) {}

    /**
     * 发送通知.
     */
    public function send(Message $message, int $userId, string $channel): bool
    {
        try {
            // 检查用户偏好
            if (! $this->shouldSendNotification($message, $userId, $channel)) {
                system_message_logger()->info('Notification skipped due to user preferences', [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                ]);
                return false;
            }

            // 检查免打扰时间
            if ($this->isInDoNotDisturbTime($userId)) {
                system_message_logger()->info('Notification skipped due to do not disturb time', [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                ]);
                return false;
            }

            // 发送通知
            $result = $this->sendByChannel($message, $userId, $channel);

            // 记录发送日志
            $this->logDelivery($message, $userId, $channel, $result);

            if ($result) {
                $this->getEventDispatcher()->dispatch(new NotificationSent($message, $userId, $channel));

                system_message_logger()->info('Notification sent successfully', [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logDelivery($message, $userId, $channel, false, $e->getMessage());
            $this->getEventDispatcher()->dispatch(new NotificationFailed($message, $userId, $channel, $e->getMessage()));

            system_message_logger()->error('Failed to send notification', [
                'message_id' => $message->id,
                'user_id' => $userId,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 批量发送通知.
     */
    public function batchSend(Message $message, array $userIds, string $channel): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($userIds as $userId) {
            try {
                $sent = $this->send($message, $userId, $channel);
                if ($sent) {
                    ++$results['success'];
                } else {
                    ++$results['skipped'];
                }
                $results['details'][$userId] = $sent ? 'sent' : 'skipped';
            } catch (\Throwable $e) {
                ++$results['failed'];
                $results['details'][$userId] = 'failed: ' . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 获取用户偏好设置.
     */
    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return $this->preferenceRepository->getUserPreference($userId);
    }

    /**
     * 更新用户偏好设置.
     */
    public function updateUserPreference(int $userId, array $data): UserNotificationPreference
    {
        return $this->preferenceRepository->createOrUpdate($userId, $data);
    }

    /**
     * 重置用户偏好设置为默认值
     */
    public function resetUserPreference(int $userId): bool
    {
        return $this->preferenceRepository->resetToDefault($userId);
    }

    /**
     * 获取默认偏好设置.
     */
    public function getDefaultPreferences(): array
    {
        return [
            'channel_preferences' => config('system_message.notification.default_channels', [
                'database' => true,
                'email' => false,
                'sms' => false,
                'push' => false,
            ]),
            'type_preferences' => config('system_message.notification.default_types', [
                'system' => true,
                'announcement' => true,
                'alert' => true,
                'reminder' => true,
                'marketing' => false,
            ]),
            'do_not_disturb_enabled' => false,
            'do_not_disturb_start' => '22:00:00',
            'do_not_disturb_end' => '08:00:00',
            'min_priority' => 1,
        ];
    }

    /**
     * 更新渠道偏好设置.
     */
    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        return $this->preferenceRepository->updateChannelPreferences($userId, $channels);
    }

    /**
     * 更新消息类型偏好设置.
     */
    public function updateTypePreferences(int $userId, array $types): bool
    {
        return $this->preferenceRepository->updateTypePreferences($userId, $types);
    }

    /**
     * 设置免打扰时间.
     */
    public function setDoNotDisturbTime(int $userId, string $startTime, string $endTime, bool $enabled = true): bool
    {
        return $this->preferenceRepository->setDoNotDisturbTime($userId, $startTime, $endTime, $enabled);
    }

    /**
     * 启用/禁用免打扰.
     */
    public function toggleDoNotDisturb(int $userId, bool $enabled): bool
    {
        return $this->preferenceRepository->toggleDoNotDisturb($userId, $enabled);
    }

    /**
     * 设置最小优先级.
     */
    public function setMinPriority(int $userId, int $priority): bool
    {
        return $this->preferenceRepository->setMinPriority($userId, $priority);
    }

    /**
     * 检查是否在免打扰时间内（公开方法）.
     */
    public function isDoNotDisturbActive(int $userId): bool
    {
        return $this->isInDoNotDisturbTime($userId);
    }

    /**
     * 根据渠道发送通知.
     */
    protected function sendByChannel(Message $message, int $userId, string $channel): bool
    {
        return match ($channel) {
            'database' => $this->sendDatabaseNotification($message, $userId),
            'email' => $this->sendEmailNotification($message, $userId),
            'sms' => $this->sendSmsNotification($message, $userId),
            'push' => $this->sendPushNotification($message, $userId),
            default => throw new \InvalidArgumentException("Unsupported notification channel: {$channel}"),
        };
    }

    /**
     * 发送数据库通知（站内信）.
     */
    protected function sendDatabaseNotification(Message $message, int $userId): bool
    {
        // 数据库通知已经在 MessageService 中处理
        // 这里只需要返回 true 表示成功
        return true;
    }

    /**
     * 发送邮件通知.
     */
    protected function sendEmailNotification(Message $message, int $userId): bool
    {
        try {
            $user = $this->getUserById($userId);
            if (! $user || ! $user->email) {
                return false;
            }

            $mailService = $this->getMailService();
            $subject = $message->title;
            $content = $this->formatEmailContent($message);

            return $mailService->send($user->email, $subject, $content);
        } catch (\Throwable $e) {
            system_message_logger()->error('Email notification failed', [
                'message_id' => $message->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 发送短信通知.
     */
    protected function sendSmsNotification(Message $message, int $userId): bool
    {
        try {
            $user = $this->getUserById($userId);
            if (! $user || ! $user->phone) {
                return false;
            }

            $smsService = $this->getSmsService();
            $content = $this->formatSmsContent($message);

            return $smsService->send($user->phone, $content);
        } catch (\Throwable $e) {
            system_message_logger()->error('SMS notification failed', [
                'message_id' => $message->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 发送推送通知.
     */
    protected function sendPushNotification(Message $message, int $userId): bool
    {
        try {
            $pushService = $this->getPushService();

            $data = [
                'title' => $message->title,
                'body' => $this->formatPushContent($message),
                'data' => [
                    'message_id' => $message->id,
                    'type' => $message->type,
                ],
            ];

            return $pushService->sendToUser($userId, $data);
        } catch (\Throwable $e) {
            system_message_logger()->error('Push notification failed', [
                'message_id' => $message->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 检查是否应该发送通知.
     */
    protected function shouldSendNotification(Message $message, int $userId, string $channel): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);

        if (! $preference) {
            return $this->getDefaultChannelSetting($channel);
        }

        $channelEnabled = $preference->getChannelSetting($channel);
        if (! $channelEnabled) {
            return false;
        }

        $typeEnabled = $preference->getTypeSetting($message->type);
        if (! $typeEnabled) {
            return false;
        }

        $minPriority = $preference->min_priority ?? 1;
        if ($message->priority < $minPriority) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否在免打扰时间.
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
     * 记录发送日志.
     */
    protected function logDelivery(Message $message, int $userId, string $channel, bool $success, ?string $error = null): void
    {
        MessageDeliveryLog::create([
            'message_id' => $message->id,
            'user_id' => $userId,
            'channel' => $channel,
            'status' => $success ? MessageDeliveryLog::STATUS_DELIVERED : MessageDeliveryLog::STATUS_FAILED,
            'error_message' => $error,
            'sent_at' => Carbon::now(),
        ]);
    }

    /**
     * 格式化邮件内容.
     */
    protected function formatEmailContent(Message $message): string
    {
        $template = config('system_message.email.template', 'default');

        return view($template, [
            'message' => $message,
            'title' => $message->title,
            'content' => $message->content,
            'type' => $message->type,
            'priority' => $message->priority,
        ])->render();
    }

    /**
     * 格式化短信内容.
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
     * 格式化推送内容.
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
     * 获取默认渠道设置.
     */
    protected function getDefaultChannelSetting(string $channel): bool
    {
        $defaults = config('system_message.notification.default_channels', [
            'database' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ]);

        return $defaults[$channel] ?? false;
    }

    /**
     * 获取用户信息.
     */
    protected function getUserById(int $userId)
    {
        return null;
    }

    /**
     * 获取邮件服务
     */
    protected function getMailService()
    {
        throw new \RuntimeException('Mail service not implemented');
    }

    /**
     * 获取短信服务
     */
    protected function getSmsService()
    {
        throw new \RuntimeException('SMS service not implemented');
    }

    /**
     * 获取推送服务
     */
    protected function getPushService()
    {
        throw new \RuntimeException('Push service not implemented');
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
