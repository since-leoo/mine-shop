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
 * 消息发送后事件.
 */
class MessageSent
{
    public function __construct(
        public readonly Message $message
    ) {}
}
