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
 * 警报通知模板
 *
 * 使用示例:
 * ```php
 * use Plugin\Since\SystemMessage\Template\AlertNotification;
 * use Plugin\Since\SystemMessage\Facade\SystemMessage;
 *
 * // 发送给管理员
 * $alert = new AlertNotification(
 *     title: '服务器异常',
 *     content: 'CPU使用率超过90%',
 *     userIds: [1] // 管理员ID
 * );
 * SystemMessage::sendTemplate($alert);
 * ```
 */
class AlertNotification extends AbstractMessageTemplate
{
    /**
     * @param string $title 警报标题
     * @param string $content 警报内容
     * @param array $userIds 接收用户ID列表
     */
    public function __construct(
        protected string $title,
        protected string $content,
        protected array $userIds = []
    ) {}

    public function getTitle(): string
    {
        return '⚠️ ' . $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): MessageType
    {
        return MessageType::ALERT;
    }

    public function getChannels(): array
    {
        return [MessageChannel::DATABASE, MessageChannel::EMAIL];
    }

    public function getPriority(): int
    {
        return 5; // 最高优先级
    }

    protected function recipients(): array
    {
        return $this->userIds;
    }
}
