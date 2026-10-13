<?php

declare(strict_types=1);

namespace Plugin\SystemMessage\Infrastructure\Adapter;

use App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface;
use Plugin\SystemMessage\Contract\SystemMessageInterface;

final class SystemNotificationAdapter implements SystemNotificationInterface
{
    public function notify(string $title, string $content): void
    {
        SystemMessageInterface::notify($title, $content);
    }

    public function broadcast(string $title, string $content): void
    {
        SystemMessageInterface::sendToAll($title, $content);
    }
}
