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
 * 消息模板接口.
 *
 * 开发者只需实现此接口，定义消息的内容和发送规则
 */
interface MessageTemplateInterface
{
    /**
     * 获取消息标题.
     */
    public function getTitle(): string;

    /**
     * 获取消息内容.
     */
    public function getContent(): string;

    /**
     * 获取消息类型.
     */
    public function getType(): MessageType;

    /**
     * 获取收件人类型.
     */
    public function getRecipientType(): RecipientType;

    /**
     * 获取收件人ID列表
     * 当 getRecipientType() 返回 RecipientType::ALL 时可返回空数组.
     */
    public function getRecipientIds(): array;

    /**
     * 获取发送渠道.
     * @return MessageChannel[]
     */
    public function getChannels(): array;

    /**
     * 获取优先级 (1-5, 5最高).
     */
    public function getPriority(): int;

    /**
     * 获取额外数据.
     */
    public function getExtra(): array;

    /**
     * 是否立即发送
     */
    public function shouldSendImmediately(): bool;
}
