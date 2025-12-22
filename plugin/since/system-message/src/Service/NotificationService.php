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
use Plugin\Since\SystemMessage\Event\NotificationSent;
use Plugin\Since\SystemMessage\Event\NotificationFailed;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\MessageDeliveryLog;
use Plugin\Since\SystemMessage\Model\UserNotificationPreference;
use Plugin\Since\SystemMessage\Repository\UserPreferenceRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Plugin\Since\SystemMessage\Service\SocketIOService;

class NotificationService
{
    protected UserPreferenceRepository $preferenceRepository;
    protected EventDispatcherInterface $eventDispatcher;
    protected SocketIOService $socketIOService;

    public function __construct(
        UserPreferenceRepository $preferenceRepository,
        EventDispatcherInterface $eventDispatcher,
        SocketIOService $socketIOService
    ) {
        $this->preferenceRepository = $preferenceRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->socketIOService = $socketIOService;
    }

    /**
     * 发送通知
     */
    public function send(Message $message, int $userId, string $channel): bool
    {
        try {
            // 检查用户偏好
            if (!$this->shouldSendNotification($message, $userId, $channel)) {
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
                // 触发发送成功事件
                $this->eventDispatcher->dispatch(new NotificationSent($message, $userId, $channel));
                
                system_message_logger()->info('Notification sent successfully', [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'channel' => $channel,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            // 记录失败日志
            $this->logDelivery($message, $userId, $channel, false, $e->getMessage());

            // 触发发送失败事件
            $this->eventDispatcher->dispatch(new NotificationFailed($message, $userId, $channel, $e->getMessage()));

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
     * 批量发送通知
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
                    $results['success']++;
                } else {
                    $results['skipped']++;
                }
                $results['details'][$userId] = $sent ? 'sent' : 'skipped';
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['details'][$userId] = 'failed: ' . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 根据渠道发送通知
     */
    protected function sendByChannel(Message $message, int $userId, string $channel): bool
    {
        switch ($channel) {
            case 'websocket':
            case 'socketio':
                return $this->sendSocketIONotification($message, $userId);
            case 'email':
                return $this->sendEmailNotification($message, $userId);
            case 'sms':
                return $this->sendSmsNotification($message, $userId);
            case 'push':
                return $this->sendPushNotification($message, $userId);
            default:
                throw new \InvalidArgumentException("Unsupported notification channel: {$channel}");
        }
    }

    /**
     * 发送Socket.IO通知
     */
    protected function sendSocketIONotification(Message $message, int $userId): bool
    {
        try {
            $data = [
                'type' => 'message_notification',
                'message_id' => $message->id,
                'title' => $message->title,
                'content' => $message->content,
                'message_type' => $message->type,
                'priority' => $message->priority,
                'created_at' => $message->created_at->toISOString(),
            ];

            return $this->socketIOService->sendToUser($userId, $data);
        } catch (\Throwable $e) {
            system_message_logger()->error('Socket.IO notification failed', [
                'message_id' => $message->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 发送邮件通知
     */
    protected function sendEmailNotification(Message $message, int $userId): bool
    {
        try {
            // 获取用户邮箱
            $user = $this->getUserById($userId);
            if (!$user || !$user->email) {
                return false;
            }

            // 获取邮件服务
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
     * 发送短信通知
     */
    protected function sendSmsNotification(Message $message, int $userId): bool
    {
        try {
            // 获取用户手机号
            $user = $this->getUserById($userId);
            if (!$user || !$user->phone) {
                return false;
            }

            // 获取短信服务
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
     * 发送推送通知
     */
    protected function sendPushNotification(Message $message, int $userId): bool
    {
        try {
            // 获取推送服务
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
     * 检查是否应该发送通知
     */
    protected function shouldSendNotification(Message $message, int $userId, string $channel): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        
        if (!$preference) {
            // 如果没有偏好设置，使用默认设置
            return $this->getDefaultChannelSetting($channel);
        }

        // 检查渠道是否启用
        $channelEnabled = $preference->getChannelSetting($channel);
        if (!$channelEnabled) {
            return false;
        }

        // 检查消息类型是否启用
        $typeEnabled = $preference->getTypeSetting($message->type);
        if (!$typeEnabled) {
            return false;
        }

        // 检查优先级过滤
        $minPriority = $preference->min_priority ?? 1;
        if ($message->priority < $minPriority) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否在免打扰时间
     */
    protected function isInDoNotDisturbTime(int $userId): bool
    {
        $preference = $this->preferenceRepository->getUserPreference($userId);
        
        if (!$preference || !$preference->do_not_disturb_enabled) {
            return false;
        }

        $now = Carbon::now();
        $startTime = Carbon::createFromTimeString($preference->do_not_disturb_start ?? '22:00:00');
        $endTime = Carbon::createFromTimeString($preference->do_not_disturb_end ?? '08:00:00');

        // 处理跨天的情况
        if ($startTime->greaterThan($endTime)) {
            return $now->greaterThanOrEqualTo($startTime) || $now->lessThanOrEqualTo($endTime);
        }

        return $now->between($startTime, $endTime);
    }

    /**
     * 记录发送日志
     */
    protected function logDelivery(Message $message, int $userId, string $channel, bool $success, string $error = null): void
    {
        MessageDeliveryLog::create([
            'message_id' => $message->id,
            'user_id' => $userId,
            'channel' => $channel,
            'status' => $success ? MessageDeliveryLog::STATUS_SUCCESS : MessageDeliveryLog::STATUS_FAILED,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }

    /**
     * 格式化邮件内容
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
     * 格式化短信内容
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
     * 格式化推送内容
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
     * 获取默认渠道设置
     */
    protected function getDefaultChannelSetting(string $channel): bool
    {
        $defaults = config('system_message.notification.default_channels', [
            'websocket' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ]);

        return $defaults[$channel] ?? false;
    }

    /**
     * 获取用户信息
     */
    protected function getUserById(int $userId)
    {
        // 这里应该调用用户服务获取用户信息
        // 暂时返回null，实际实现时需要注入用户服务
        return null;
    }

    /**
     * 获取邮件服务
     */
    protected function getMailService()
    {
        // 这里应该返回邮件服务实例
        // 暂时抛出异常，实际实现时需要注入邮件服务
        throw new \RuntimeException('Mail service not implemented');
    }

    /**
     * 获取短信服务
     */
    protected function getSmsService()
    {
        // 这里应该返回短信服务实例
        // 暂时抛出异常，实际实现时需要注入短信服务
        throw new \RuntimeException('SMS service not implemented');
    }

    /**
     * 获取推送服务
     */
    protected function getPushService()
    {
        // 这里应该返回推送服务实例
        // 暂时抛出异常，实际实现时需要注入推送服务
        throw new \RuntimeException('Push service not implemented');
    }
}