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
 * 消息事件基类
 */
abstract class MessageEvent
{
    public function __construct(
        public readonly Message $message
    ) {}
}

/**
 * 消息创建事件
 */
class MessageCreated extends MessageEvent
{
}

/**
 * 消息发送前事件
 */
class MessageSending extends MessageEvent
{
}

/**
 * 消息发送后事件
 */
class MessageSent extends MessageEvent
{
}

/**
 * 消息发送失败事件
 */
class MessageSendFailed extends MessageEvent
{
    public function __construct(
        Message $message,
        public readonly string $error
    ) {
        parent::__construct($message);
    }
}

/**
 * 消息更新事件
 */
class MessageUpdated extends MessageEvent
{
    public function __construct(
        Message $message,
        public readonly array $changes
    ) {
        parent::__construct($message);
    }
}

/**
 * 消息删除事件
 */
class MessageDeleted extends MessageEvent
{
}