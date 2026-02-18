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

namespace App\Domain\Infrastructure\SystemMessage\Event;

use App\Infrastructure\Model\SystemMessage\Message;

/**
 * 通知发送成功事件.
 */
class NotificationSent
{
    public function __construct(
        public readonly Message $message,
        public readonly int $userId,
        public readonly string $channel
    ) {}
}
