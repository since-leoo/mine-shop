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

namespace App\Domain\Infrastructure\SystemMessage\Facade;

use App\Domain\Infrastructure\SystemMessage\Contract\MessageTemplateInterface;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageStatus;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Enum\RecipientType;
use App\Domain\Infrastructure\SystemMessage\Event\TemplateMessageEvent;
use App\Domain\Infrastructure\SystemMessage\Service\MessageService;
use App\Domain\Infrastructure\SystemMessage\Service\NotificationService;
use App\Domain\Infrastructure\SystemMessage\Service\TemplateService;
use App\Infrastructure\Model\SystemMessage\Message;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * 系统消息门面类.
 *
 * 提供简单易用的静态方法，让其他开发者无需实例化即可使用系统消息功能
 */
class SystemMessage
{
    /**
     * 动态调用方法.
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $service = static::getMessageService();

        if (method_exists($service, $method)) {
            return $service->{$method}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist");
    }

    /**
     * 通过模板发送消息 - 推荐方式.
     */
    public static function sendTemplate(
        MessageTemplateInterface $template,
        bool $useQueue = false,
        int $queueDelay = 0,
        ?string $queueName = null
    ): TemplateMessageEvent {
        $event = new TemplateMessageEvent($template, $useQueue, $queueDelay, $queueName);
        static::getEventDispatcher()->dispatch($event);
        return $event;
    }

    /**
     * 创建消息.
     */
    public static function create(array $data): Message
    {
        return static::getMessageService()->create($data);
    }

    /**
     * 发送消息.
     */
    public static function send(int $messageId): bool
    {
        return static::getMessageService()->send($messageId);
    }

    /**
     * 发送消息给指定用户.
     */
    public static function sendToUser(
        array|int $userIds,
        string $title,
        string $content,
        MessageType|string $type = MessageType::SYSTEM,
        array $options = []
    ): Message {
        $userIds = \is_array($userIds) ? $userIds : [$userIds];
        $typeValue = $type instanceof MessageType ? $type->value : $type;

        $data = array_merge([
            'title' => $title,
            'content' => $content,
            'type' => $typeValue,
            'recipient_type' => RecipientType::USER->value,
            'recipient_ids' => $userIds,
            'status' => MessageStatus::DRAFT->value,
        ], $options);

        $message = static::create($data);
        static::send($message->id);

        return $message;
    }

    /**
     * 发送消息给指定角色.
     */
    public static function sendToRole(
        array|int $roleIds,
        string $title,
        string $content,
        MessageType|string $type = MessageType::SYSTEM,
        array $options = []
    ): Message {
        $roleIds = \is_array($roleIds) ? $roleIds : [$roleIds];
        $typeValue = $type instanceof MessageType ? $type->value : $type;

        $data = array_merge([
            'title' => $title,
            'content' => $content,
            'type' => $typeValue,
            'recipient_type' => RecipientType::ROLE->value,
            'recipient_ids' => $roleIds,
            'status' => MessageStatus::DRAFT->value,
        ], $options);

        $message = static::create($data);
        static::send($message->id);

        return $message;
    }

    /**
     * 发送消息给所有用户.
     */
    public static function sendToAll(
        string $title,
        string $content,
        MessageType|string $type = MessageType::SYSTEM,
        array $options = []
    ): Message {
        $typeValue = $type instanceof MessageType ? $type->value : $type;

        $data = array_merge([
            'title' => $title,
            'content' => $content,
            'type' => $typeValue,
            'recipient_type' => RecipientType::ALL->value,
            'status' => MessageStatus::DRAFT->value,
        ], $options);

        $message = static::create($data);
        static::send($message->id);

        return $message;
    }

    /**
     * 使用模板发送消息.
     */
    public static function sendWithTemplate(
        int $templateId,
        array $variables,
        array $recipients,
        array $options = []
    ): Message {
        $template = static::getTemplateService()->getById($templateId);
        if (! $template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $rendered = $template->render($variables);

        $data = array_merge([
            'title' => $rendered['title'],
            'content' => $rendered['content'],
            'type' => $template->type,
            'template_id' => $templateId,
            'template_variables' => $variables,
            'status' => MessageStatus::DRAFT->value,
        ], $recipients, $options);

        $message = static::create($data);
        static::send($message->id);

        return $message;
    }

    /**
     * 调度消息（定时发送）.
     */
    public static function schedule(array $data, Carbon $scheduledAt): Message
    {
        $data['scheduled_at'] = $scheduledAt;
        $data['status'] = MessageStatus::SCHEDULED->value;

        return static::create($data);
    }

    /**
     * 发送系统通知.
     */
    public static function notify(
        string $title,
        string $content,
        array|int|null $userIds = null,
        array $channels = ['database']
    ): Message {
        $options = ['channels' => $channels];

        if ($userIds !== null) {
            return static::sendToUser($userIds, $title, $content, MessageType::SYSTEM, $options);
        }

        return static::sendToAll($title, $content, MessageType::SYSTEM, $options);
    }

    /**
     * 发送公告.
     */
    public static function announce(
        string $title,
        string $content,
        array $channels = ['websocket', 'email']
    ): Message {
        return static::sendToAll($title, $content, MessageType::ANNOUNCEMENT, [
            'channels' => $channels,
        ]);
    }

    /**
     * 发送警报.
     */
    public static function alert(
        string $title,
        string $content,
        array|int|null $userIds = null,
        array $channels = ['websocket', 'email']
    ): Message {
        if ($userIds !== null) {
            return static::sendToUser($userIds, $title, $content, MessageType::ALERT, [
                'channels' => $channels,
                'priority' => 5,
            ]);
        }

        return static::sendToAll($title, $content, MessageType::ALERT, [
            'channels' => $channels,
            'priority' => 5,
        ]);
    }

    /**
     * 发送提醒.
     */
    public static function remind(
        string $title,
        string $content,
        array|int $userIds,
        ?Carbon $remindAt = null
    ): Message {
        $options = [
            'channels' => ['websocket'],
            'priority' => 2,
        ];

        if ($remindAt) {
            $data = [
                'title' => $title,
                'content' => $content,
                'type' => MessageType::REMINDER->value,
                'recipient_type' => RecipientType::USER->value,
                'recipient_ids' => \is_array($userIds) ? $userIds : [$userIds],
                'scheduled_at' => $remindAt,
                'status' => MessageStatus::SCHEDULED->value,
            ];

            return static::create(array_merge($data, $options));
        }

        return static::sendToUser($userIds, $title, $content, MessageType::REMINDER, $options);
    }

    /**
     * 获取用户未读消息数量.
     */
    public static function getUnreadCount(int $userId): int
    {
        return static::getMessageService()->getUnreadCount($userId);
    }

    /**
     * 标记消息为已读.
     */
    public static function markAsRead(int $messageId, int $userId): bool
    {
        return static::getMessageService()->markAsRead($messageId, $userId);
    }

    /**
     * 批量标记消息为已读.
     */
    public static function batchMarkAsRead(array $messageIds, int $userId): int
    {
        return static::getMessageService()->batchMarkAsRead($messageIds, $userId);
    }

    /**
     * 删除用户消息.
     */
    public static function deleteUserMessage(int $messageId, int $userId): bool
    {
        return static::getMessageService()->deleteUserMessage($messageId, $userId);
    }

    /**
     * 获取用户消息列表.
     */
    public static function getUserMessages(int $userId, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        return static::getMessageService()->getUserMessages($userId, $filters, $page, $pageSize);
    }

    /**
     * 获取消息服务实例.
     */
    protected static function getMessageService(): MessageService
    {
        return ApplicationContext::getContainer()->get(MessageService::class);
    }

    /**
     * 获取通知服务实例.
     */
    protected static function getNotificationService(): NotificationService
    {
        return ApplicationContext::getContainer()->get(NotificationService::class);
    }

    /**
     * 获取模板服务实例.
     */
    protected static function getTemplateService(): TemplateService
    {
        return ApplicationContext::getContainer()->get(TemplateService::class);
    }

    /**
     * 获取事件分发器.
     */
    protected static function getEventDispatcher(): EventDispatcherInterface
    {
        return ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
    }
}
