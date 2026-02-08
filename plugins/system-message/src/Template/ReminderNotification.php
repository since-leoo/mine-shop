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
 * 提醒通知模板
 *
 * 使用示例:
 * ```php
 * use Plugin\Since\SystemMessage\Template\ReminderNotification;
 * use Plugin\Since\SystemMessage\Facade\SystemMessage;
 *
 * $reminder = new ReminderNotification(
 *     title: '待办提醒',
 *     content: '您有3个待处理的审批任务',
 *     userId: 1
 * );
 * SystemMessage::sendTemplate($reminder);
 * ```
 */
class ReminderNotification extends AbstractMessageTemplate
{
    /**
     * @param string $title 提醒标题
     * @param string $content 提醒内容
     * @param int $userId 接收用户ID
     * @param array $extra 额外数据（如跳转链接等）
     */
    public function __construct(
        protected string $title,
        protected string $content,
        protected int $userId,
        protected array $extra = []
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
        return MessageType::REMINDER;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function getPriority(): int
    {
        return 2;
    }

    protected function recipients(): array
    {
        return [$this->userId];
    }
}
