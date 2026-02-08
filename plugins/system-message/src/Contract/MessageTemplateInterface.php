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

namespace Plugin\Since\SystemMessage\Contract;

use Plugin\Since\SystemMessage\Enum\MessageChannel;
use Plugin\Since\SystemMessage\Enum\MessageType;
use Plugin\Since\SystemMessage\Enum\RecipientType;

/**
 * 消息模板接口.
 *
 * 开发者只需实现此接口，定义消息的内容和发送规则
 *
 * 使用示例:
 * ```php
 * class OrderPaidNotification implements MessageTemplateInterface
 * {
 *     public function __construct(
 *         private Order $order
 *     ) {}
 *
 *     public function getTitle(): string
 *     {
 *         return "订单支付成功 #{$this->order->order_no}";
 *     }
 *
 *     public function getContent(): string
 *     {
 *         return "您的订单 {$this->order->order_no} 已支付成功，金额：{$this->order->amount}元";
 *     }
 *
 *     public function getType(): MessageType
 *     {
 *         return MessageType::SYSTEM;
 *     }
 *
 *     public function getRecipientType(): RecipientType
 *     {
 *         return RecipientType::USER;
 *     }
 *
 *     public function getRecipientIds(): array
 *     {
 *         return [$this->order->user_id];
 *     }
 * }
 *
 * // 发送
 * SystemMessage::sendTemplate(new OrderPaidNotification($order));
 * ```
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
