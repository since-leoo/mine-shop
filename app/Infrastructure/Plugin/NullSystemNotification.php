<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface;

final class NullSystemNotification implements SystemNotificationInterface
{
    public function notify(string $title, string $content): void {}

    public function broadcast(string $title, string $content): void {}
}
