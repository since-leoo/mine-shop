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

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\Message;

/**
 * 通知事件基类
 */
abstract class NotificationEvent
{
    public function __construct(
        public readonly Message $message,
        public readonly int $userId,
        public readonly string $channel
    ) {}
}

/**
 * 通知发送成功事件
 */
class NotificationSent extends NotificationEvent
{
}

/**
 * 通知发送失败事件
 */
class NotificationFailed extends NotificationEvent
{
    public function __construct(
        Message $message,
        int $userId,
        string $channel,
        public readonly string $error
    ) {
        parent::__construct($message, $userId, $channel);
    }
}