<?php

declare(strict_types=1);

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\Message;

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
