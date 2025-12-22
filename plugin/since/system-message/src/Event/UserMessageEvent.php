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

use App\Model\Permission\User;
use Plugin\Since\SystemMessage\Model\Message;
use Plugin\Since\SystemMessage\Model\UserMessage;

/**
 * 用户消息事件基类
 */
abstract class UserMessageEvent
{
    public function __construct(
        public readonly UserMessage $userMessage
    ) {}
}

/**
 * 用户消息已读事件
 */
class UserMessageRead extends UserMessageEvent
{
}

/**
 * 用户消息删除事件
 */
class UserMessageDeleted extends UserMessageEvent
{
}

/**
 * 用户消息接收事件
 */
class UserMessageReceived extends UserMessageEvent
{
}