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

namespace App\Domain\Infrastructure\SystemMessage\Contract;

use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Enum\RecipientType;

/**
 * 消息模板抽象基类.
 *
 * 提供默认实现，开发者只需覆盖必要的方法
 */
abstract class AbstractMessageTemplate implements MessageTemplateInterface
{
    /**
     * 获取消息标题 - 必须实现.
     */
    abstract public function getTitle(): string;

    /**
     * 获取消息内容 - 必须实现.
     */
    abstract public function getContent(): string;

    /**
     * 获取消息类型
     * 默认为系统消息.
     */
    public function getType(): MessageType
    {
        return MessageType::SYSTEM;
    }

    /**
     * 获取收件人类型
     * 默认发送给所有用户.
     */
    public function getRecipientType(): RecipientType
    {
        $recipients = $this->recipients();
        if (! empty($recipients)) {
            return RecipientType::USER;
        }
        return RecipientType::ALL;
    }

    /**
     * 获取收件人ID列表.
     */
    public function getRecipientIds(): array
    {
        return $this->recipients();
    }

    /**
     * 获取发送渠道
     * 默认使用 SocketIO.
     */
    public function getChannels(): array
    {
        return [MessageChannel::SOCKETIO];
    }

    /**
     * 获取优先级
     * 默认中等优先级.
     */
    public function getPriority(): int
    {
        return 3;
    }

    /**
     * 获取额外数据.
     */
    public function getExtra(): array
    {
        return [];
    }

    /**
     * 是否立即发送
     * 默认立即发送
     */
    public function shouldSendImmediately(): bool
    {
        return true;
    }

    /**
     * 获取收件人ID - 子类可覆盖
     * 快捷方法，用于简化 getRecipientIds 的实现.
     */
    protected function recipients(): array
    {
        return [];
    }
}
