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

namespace App\Domain\Infrastructure\SystemMessage\Event;

use App\Domain\Infrastructure\SystemMessage\Contract\MessageTemplateInterface;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;

/**
 * 模板消息事件.
 *
 * 通过传入实现了 MessageTemplateInterface 的模板对象来发送消息
 */
class TemplateMessageEvent
{
    /**
     * 创建的消息ID（事件处理后填充）.
     */
    public ?int $messageId = null;

    /**
     * 是否发送成功
     */
    public bool $success = false;

    /**
     * 错误信息.
     */
    public ?string $error = null;

    /**
     * @param MessageTemplateInterface $template 消息模板实例
     * @param bool $useQueue 是否使用队列异步发送
     * @param int $queueDelay 队列延迟秒数
     * @param null|string $queueName 指定队列名称
     */
    public function __construct(
        public readonly MessageTemplateInterface $template,
        public readonly bool $useQueue = false,
        public readonly int $queueDelay = 0,
        public readonly ?string $queueName = null
    ) {}

    /**
     * 转换为消息数据数组.
     */
    public function toMessageData(): array
    {
        $channels = array_map(
            static fn (MessageChannel $channel) => $channel->value,
            $this->template->getChannels()
        );

        return [
            'title' => $this->template->getTitle(),
            'content' => $this->template->getContent(),
            'type' => $this->template->getType()->value,
            'recipient_type' => $this->template->getRecipientType()->value,
            'recipient_ids' => $this->template->getRecipientIds(),
            'priority' => $this->template->getPriority(),
            'channels' => $channels,
            'extra' => $this->template->getExtra(),
        ];
    }

    /**
     * 是否立即发送
     */
    public function shouldSendImmediately(): bool
    {
        return $this->template->shouldSendImmediately();
    }
}
