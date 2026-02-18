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
 * 通知发送失败事件.
 */
class NotificationFailed
{
    public function __construct(
        public readonly Message $message,
        public readonly int $userId,
        public readonly string $channel,
        public readonly string $error
    ) {}
}
