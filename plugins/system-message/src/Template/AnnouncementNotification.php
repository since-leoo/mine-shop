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
use Plugin\Since\SystemMessage\Enum\MessageChannel;
use Plugin\Since\SystemMessage\Enum\MessageType;

/**
 * 公告通知模板
 *
 * 使用示例:
 * ```php
 * use Plugin\Since\SystemMessage\Template\AnnouncementNotification;
 * use Plugin\Since\SystemMessage\Facade\SystemMessage;
 *
 * $announcement = new AnnouncementNotification(
 *     title: '重要公告',
 *     content: '公司将于下周一举行年会'
 * );
 * SystemMessage::sendTemplate($announcement);
 * ```
 */
class AnnouncementNotification extends AbstractMessageTemplate
{
    /**
     * @param string $title 公告标题
     * @param string $content 公告内容
     * @param bool $sendEmail 是否同时发送邮件
     */
    public function __construct(
        protected string $title,
        protected string $content,
        protected bool $sendEmail = false
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
        return MessageType::ANNOUNCEMENT;
    }

    public function getChannels(): array
    {
        $channels = [MessageChannel::SOCKETIO];
        if ($this->sendEmail) {
            $channels[] = MessageChannel::EMAIL;
        }
        return $channels;
    }

    public function getPriority(): int
    {
        return 4; // 公告优先级较高
    }
}
