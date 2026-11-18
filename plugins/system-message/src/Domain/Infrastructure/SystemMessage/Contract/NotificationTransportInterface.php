<?php

declare(strict_types=1);

namespace Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract;

interface NotificationTransportInterface
{
    public function email(string $recipient, string $title, string $html): void;

    public function gateway(string $channel, int $userId, array $payload): void;
}
