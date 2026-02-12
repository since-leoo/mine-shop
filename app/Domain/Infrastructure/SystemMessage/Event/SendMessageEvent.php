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

use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Enum\RecipientType;

/**
 * 发送消息事件 - 供开发者使用.
 */
class SendMessageEvent
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
     * @param string $title 消息标题
     * @param string $content 消息内容（支持HTML）
     * @param MessageType $type 消息类型
     * @param RecipientType $recipientType 收件人类型
     * @param array $recipientIds 收件人ID列表（当 recipientType 不是 ALL 时需要）
     * @param int $priority 优先级 1-5，5最高
     * @param array<MessageChannel|string> $channels 发送渠道
     * @param array $extra 额外数据
     * @param bool $sendImmediately 是否立即发送，false则保存为草稿
     * @param bool $useQueue 是否使用队列异步发送（推荐用于大量用户）
     * @param int $queueDelay 队列延迟秒数（仅当 useQueue=true 时有效）
     * @param null|string $queueName 指定队列名称（为空则使用默认队列）
     */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly MessageType $type = MessageType::SYSTEM,
        public readonly RecipientType $recipientType = RecipientType::ALL,
        public readonly array $recipientIds = [],
        public readonly int $priority = 3,
        public readonly array $channels = [],
        public readonly array $extra = [],
        public readonly bool $sendImmediately = true,
        public readonly bool $useQueue = false,
        public readonly int $queueDelay = 0,
        public readonly ?string $queueName = null
    ) {}

    /**
     * 转换为消息数据数组.
     */
    public function toMessageData(): array
    {
        // 处理渠道，支持 Enum 和字符串
        $channels = array_map(static function ($channel) {
            return $channel instanceof MessageChannel ? $channel->value : $channel;
        }, $this->channels ?: [MessageChannel::DATABASE]);

        return [
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type->value,
            'recipient_type' => $this->recipientType->value,
            'recipient_ids' => $this->recipientIds,
            'priority' => $this->priority,
            'channels' => $channels,
            'extra' => $this->extra,
        ];
    }
}
