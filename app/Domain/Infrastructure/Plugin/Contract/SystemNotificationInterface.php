<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** Optional operations notifications; implementations own delivery channels. */
interface SystemNotificationInterface
{
    public function notify(string $title, string $content): void;

    public function broadcast(string $title, string $content): void;
}
