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

namespace Plugin\Since\SystemMessage\Template;

use Plugin\Since\SystemMessage\Contract\AbstractMessageTemplate;
use Plugin\Since\SystemMessage\Enum\MessageType;

/**
 * 系统通知模板
 *
 * 使用示例:
 * ```php
 * use Plugin\Since\SystemMessage\Template\SystemNotification;
 * use Plugin\Since\SystemMessage\Facade\SystemMessage;
 *
 * // 发送给指定用户
 * $notification = new SystemNotification(
 *     title: '系统维护通知',
 *     content: '系统将于今晚22:00进行维护',
 *     userIds: [1, 2, 3]
 * );
 * SystemMessage::sendTemplate($notification);
 *
 * // 发送给所有用户
 * $notification = new SystemNotification(
 *     title: '系统更新',
 *     content: '系统已更新到最新版本'
 * );
 * SystemMessage::sendTemplate($notification);
 * ```
 */
class SystemNotification extends AbstractMessageTemplate
{
    /**
     * @param string $title 通知标题
     * @param string $content 通知内容
     * @param array $userIds 接收用户ID列表，为空则发送给所有用户
     * @param int $priority 优先级 1-5
     */
    public function __construct(
        protected string $title,
        protected string $content,
        protected array $userIds = [],
        protected int $priority = 3
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): MessageType
    {
        return MessageType::SYSTEM;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    protected function recipients(): array
    {
        return $this->userIds;
    }
}
